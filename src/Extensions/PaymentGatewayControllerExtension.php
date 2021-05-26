<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\JWTProcessor;
use Omnipay\NSWGOVCPP\JWTDecodeException;
use Omnipay\NSWGOVCPP\UnprocessableEntityException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Provides extension handling to modify/retrieve payment data in the PaymentGatewayController
 *
 * @author James
 */
class PaymentGatewayControllerExtension extends Extension
{

    /**
     * > updatePaymentActionFromRequest called for every request to the PaymentGatewayController.
     * > Can be used to set the payment action from the incoming request-data. Sometimes needed for static routes
     *
     * Only the CPP 'cancel' and 'success' actions are supported
     */
    public function updatePaymentActionFromRequest(&$action, OmnipayPayment $payment, HTTPRequest $request)
    {
        switch($action) {
            case 'cancel':
                // payer cancelled the payment, internal action is also called 'cancel'
                $action = 'cancel';
                break;
            case 'success':
                // successful payment made by payer, translate to internal action 'complete'
                $action ='complete';
                break;
            default:
                // default to no action, should trigger a 404 error
                $action = '';
        }
    }

    /**
     * On completion, return the Omnipay Payment record
     * This is used to handle payment completion notifications from the CPP
     * This is called prior to any complete purchase extensions and needs to do the JWT decode
     *
     * > updatePaymentFromRequest called for every request that goes to the PaymentGatewayController.
     * > Can be used to return a Payment object from the request data. Needed for enabling static routes

     * @return null|OmnipayPayment
     */
    public function updatePaymentFromRequest(HTTPRequest $request, $gateway = null)
    {

        /*
         * Silently ignore requests for non NSWGOVCPP gateway
          * This can occur if another payment gateway is in use
         */
        if (!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            return;
        }

        // act based on status
        $status = strtolower($request->param('Status'));
        switch ($status) {
            case 'cancel':

                Logger::log("Cancel payment requested", "DEBUG");

                /**
                 * customer has chosen to cancel the payment
                 * the CPP will redirect the customer back here
                 * need to store a status and redirect the customer to their order
                 */
                $paymentReference = $request->getVar('paymentReference');
                if (!$paymentReference) {
                    Logger::log("Cancel payment request but no 'paymentReference' query string value provided", "NOTICE");
                    return $this->owner->httpError(400, "No payment reference supplied");
                }
                // get payment based on paymentReference
                $cppPayment = Payment::getByPaymentReference($paymentReference);
                if (!$cppPayment || !$cppPayment->isInDB()) {
                    Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching CppPayment record found for this value", "WARNING");
                    return $this->owner->httpError(406, "Not Acceptable");
                }

                // mark the CPP payment as cancelled
                Logger::log("Mark cppPayment cancelled", "DEBUG");
                $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_CANCELLED;
                $cppPayment->write();

                $payment = $cppPayment->OmnipayPayment();
                if ($payment instanceof OmnipayPayment) {
                    $payment->Status = 'PendingPurchase';
                    $payment->write();
                    Logger::log("Returning OmnipayPayment after cancelled payment", "DEBUG");
                    return $payment;
                }

                Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                return $this->owner->httpError(406, "Not Acceptable");

                break;

            case 'success':

                /**
                 * customer sucessfully made the payment
                 * the CPP will redirect the customer back here, after the 'complete' request is made by the CPP gateway
                 */

                 $paymentReference = $request->getVar('paymentReference');
                 if (!$paymentReference) {
                     Logger::log("Successful payment request but no 'paymentReference' query string value provided", "NOTICE");
                     return $this->owner->httpError(400, "No payment reference supplied");
                 }
                 // get payment based on paymentReference
                 $cppPayment = Payment::getByPaymentReference($paymentReference);
                 if (!$cppPayment || !$cppPayment->isInDB()) {
                     Logger::log("Successful payment with PaymentReference={$paymentReference} but no matching CppPayment record found for this value", "WARNING");
                     return $this->owner->httpError(406, "Not Acceptable");
                 }

                 // for a successful payment to be received, the payment record must have the values
                 // saved from the 'complete' request
                 if(empty($cppPayment->PaymentCompletionReference)) {
                     Logger::log("Successful payment with PaymentReference={$paymentReference} has no PaymentCompletionReference", "WARNING");
                     return $this->owner->httpError(406, "Not Acceptable");
                 }

                 if(empty($cppPayment->PaymentMethod)) {
                     Logger::log("Successful payment with PaymentReference={$paymentReference} has no PaymentMethod", "WARNING");
                     return $this->owner->httpError(406, "Not Acceptable");
                 }

                 // mark the CPP payment as completed
                 $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_COMPLETED;
                 $cppPayment->write();

                 $payment = $cppPayment->OmnipayPayment();
                 if ($payment instanceof OmnipayPayment) {
                     // return the payment
                     $payment->Status = 'PendingPurchase';
                     $payment->write();
                     return $payment;
                 }

                 Logger::log("Successful payment with PaymentReference={$paymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                 return $this->owner->httpError(406, "Not Acceptable");

                break;

            case 'complete':

                try {

                    /**
                     * CPP makes the completion call to the configured agency endpoint after
                     * the payment has been successfully made.
                     * The response has a JWT which should be verified with the key shared with the agency.
                     */

                    // default response code
                    $code = 503;

                    // default error message
                    $errorMessage = $externalErrorMessage = '';

                    // get all parameters for the gateway
                    $parameters = GatewayInfo::getConfigSetting($gateway, 'parameters');

                    // decode the JWT sent back from CPP containing payment information
                    $jwtPublicKey = $parameters['jwtPublicKey'] ?? '';
                    if (empty($jwtPublicKey)) {
                        throw new \Exception("no jwtPublicKey or empty in gateway parameters");
                    }
                    $body = $request->getBody();
                    $decoded = json_decode($body, true, JSON_THROW_ON_ERROR);
                    $jwt = $decoded['token'] ?? '';
                    if (empty($jwt)) {
                        throw new \Exception("no JWT or empty in POST request from CPP");
                    }

                    $leeway = 60;//@todo configurable
                    $algos = ["RS256"];//@todo configurable?
                    // @throws JWTDecodeException|UnprocessableEntityException
                    $output = JWTProcessor::decode($jwt, $jwtPublicKey, $algos, $leeway);

                    // agencyTransactionId is used to determine the payment
                    if (empty($output->agencyTransactionId)) {
                        throw new \Exception("no agencyTransactionId in POST request from CPP");
                    }

                    // get payment based on paymentReference
                    $cppPayment = Payment::getByAgencyTransactionId($output->agencyTransactionId);
                    if (!$cppPayment || !$cppPayment->isInDB()) {
                        throw new \Exception("Could not get CPP payment for Agency txn: {$output->agencyTransactionId}");
                    }

                    // get the linked payment record and return it
                    $payment = $cppPayment->OmnipayPayment();
                    if (empty($payment)) {
                        throw new \Exception("Could not get linked OmnipayPayment payment record for CPP payment #{$cppPayment->ID}/{$output->agencyTransactionId}");
                    }

                    if ($payment->Status == 'Captured') {
                        // payment was already completed
                        // avoid CPP pinging again by sending a 422
                        throw new UnprocessableEntityException("Payment already captured");
                    }

                    // save POSTed output to the CPP payment model
                    // status will be completed as the JWT is only sent when the payment
                    // was successfully made
                    $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_COMPLETED;
                    $cppPayment->PaymentReference = $output->paymentReference ?? '';
                    $cppPayment->PaymentCompletionReference = $output->paymentCompletionReference ?? '';
                    $cppPayment->BankReference = $output->bankReference ?? '';
                    $cppPayment->PaymentMethod = $output->paymentMethod ?? '';
                    $cppPayment->write();

                    if ($payment instanceof OmnipayPayment) {
                        $payment->Status = 'PendingPurchase';
                        $payment->write();
                        return $payment;
                    }

                    // handling a NSWGOVCPP payment completion and  no payment can be found
                    throw new \Exception("updatePaymentFromRequest: payment #{$cppPayment->ID} has no omnipayPayment");
                } catch (JWTDecodeException $e) {
                    Logger::log("Caught a JWTDecodeException");
                    // Specific JWT decode error
                    // This is generally a 50x error code
                    $code = $e->getCode();
                    $errorMessage = $e->getMessage();
                    $externalErrorMessage = "Could not process the request";
                } catch (UnprocessableEntityException $e) {
                    Logger::log("Caught a UnprocessableEntityException");
                    // this exception will always trigger a 422
                    $code = 422;
                    $errorMessage = $e->getMessage();
                    $externalErrorMessage = "Could not process the request - final error";
                } catch (\Exception $e) {
                    // noop
                    $errorMessage = $e->getMessage();
                    $externalErrorMessage = "A general error has occurred";
                }

                // Error condition handling
                // sanity check on the HTTP error code
                $code = intval($code);
                if ($code < 400 || $code > 599) {
                    // ensure we use a sane 50x error code if the code provided
                    // would tell the CPP incorrect information
                    $code = 503;
                }

                if ($errorMessage) {
                    Logger::log("Payment completion exception code={$code} message={$errorMessage}", "NOTICE");
                }

                // create and output the response, and exit early
                $response = HTTPResponse::create();
                $response->setStatusCode($code);
                $response->addHeader('Content-Type', 'application/json; charset=utf-8');
                $response->setBody(json_encode(['error' => $externalErrorMessage]));
                $response->output();
                exit;//@todo move to config, enabled by default e.g for tests

                break;
            default:
                // no operation carried out
                return;
                break;
        } // end switch

    } // updatePaymentFromRequest

}
