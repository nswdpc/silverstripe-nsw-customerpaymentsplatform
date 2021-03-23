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
     */
    public function updatePaymentActionFromRequest(string $action, OmnipayPayment $payment, HTTPRequest $request) {
        Logger::log( "updatePaymentActionFromRequest:" . $action );
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
    public function updatePaymentFromRequest(HTTPRequest $request, $gateway = null) {

        /*
         * Silently ignore requests for non NSWGOVCPP gateway
          * This can occur if another payment gateway is in uss
         */
        if(!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            return;
        }

        // act based on status
        $status = strtolower($request->param('Status'));
        switch($status) {
            case 'complete':

                try {

                    // default error message
                    $errorMessage = $externalErrorMessage = '';

                    // get all parameters for the gateway
                    $parameters = GatewayInfo::getConfigSetting($gateway, 'parameters');

                    /**
                     * CPP makes the completion call to the configured agency endpoint after
                     * the payment has been successfully made.
                     * The response has a JWT which should be verified with the key shared with the agency.
                     */

                    // decode the JWT sent back from CPP containing payment information
                    $jwtPublicKey = $parameters['jwtPublicKey'] ?? '';
                    if(empty($jwtPublicKey)) {
                        throw new \Exception("no jwtPublicKey or empty in gateway parameters");
                    }
                    $body = $request->getBody();
                    $decoded = json_decode($body, true, JSON_THROW_ON_ERROR);
                    $jwt = $decoded['token'] ?? '';
                    if(empty($jwt)) {
                        throw new \Exception("no JWT or empty in POST request from CPP");
                    }

                    $leeway = 60;//@todo configurable
                    $algos = ["RS256"];//@todo configurable?
                    // @throws JWTDecodeException|UnprocessableEntityException
                    $output = JWTProcessor::decode($jwt, $jwtPublicKey, $algos, $leeway);

                    // agencyTransactionId is used to determine the payment
                    if(empty($output->agencyTransactionId)) {
                        throw new \Exception("no agencyTransactionId in POST request from CPP");
                    }

                    // get payment based on paymentReference
                    $cppPayment = Payment::getByAgencyTransactionId($output->agencyTransactionId);
                    if(!$cppPayment || !$cppPayment->isInDB()) {
                        throw new \Exception("Could not get CPP payment for Agency txn: {$output->agencyTransactionId}");
                    }

                    // get the linked payment record and return it
                    $payment = $cppPayment->OmnipayPayment();
                    if(empty($payment)) {
                        throw new \Exception("Could not get linked OmnipayPayment payment record for CPP payment #{$cppPayment->ID}/{$output->agencyTransactionId}");
                    }

                    if($payment->Status == 'Captured') {
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

                    if($payment instanceof OmnipayPayment) {
                        return $payment;
                    }

                    // handling a NSWGOVCPP payment completion and  no payment can be found
                    throw new \Exception("updatePaymentFromRequest: payment #{$cppPayment->ID} has no omnipayPayment");


                } catch (JWTDecodeException $e) {
                    Logger::log( "Caught a JWTDecodeException");
                    // Specific JWT decode error
                    // This is generally a 50x error code
                    $code = $e->getCode();
                    $errorMessage = $e->getMessage();
                    $externalErrorMessage = "Could not process the request";
                } catch (UnprocessableEntityException $e) {
                    Logger::log( "Caught a UnprocessableEntityException");
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
                if($code < 400 || $code > 599) {
                    // ensure we use a sane 50x error code if the code provided
                    // would tell the CPP incorrect information
                    $code = 503;
                }

                if($errorMessage) {
                    Logger::log( "Payment completion exception code={$code} message={$errorMessage}", "NOTICE");
                }

                // create and output the response, and exit early
                $response = HTTPResponse::create();
                $response->setStatusCode($code);
                $response->addHeader('Content-Type','application/json; charset=utf-8');
                $response->setBody(json_encode(['error' => $externalErrorMessage]));
                $response->output();
                exit;//@todo move to config, enabled by default e.g for tests

                break;
            default:
                // no operation carried out
                return;
                break;
        } // end switch

    }

}
