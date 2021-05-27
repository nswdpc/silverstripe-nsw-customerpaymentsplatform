<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\JWTDecodeException;
use Omnipay\NSWGOVCPP\UnprocessableEntityException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides extension handling to modify/retrieve payment based on the request received by the PaymentGatewayController
 *
 * 'cancel' a payer is cancelling their payment attempt or returning after a failed attempt to pay
 * 'sucess' a payer is returning after their payment was captured and completed
 * 'complete' the CPP is sending us a payment completion response for a successful payment
 *
 * @author James
 */
class PaymentGatewayControllerExtension extends Extension
{

    /**
     * > updatePaymentActionFromRequest called for every request to the PaymentGatewayController.
     * > Can be used to set the payment action from the incoming request-data. Sometimes needed for static routes
     *
     */
    public function updatePaymentActionFromRequest(&$action, OmnipayPayment $payment, HTTPRequest $request)
    {

        // verify gateway in use
        $gateway = $request->param('Gateway');
        if (!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            $action = '';
        }

        switch($action) {
            case 'cancel':
                // payer cancelled the payment
                // internal action is also called 'cancel'
                $action = 'cancel';
                break;
            case 'success':
                // successful payment made by payer, the success url is the current URL
                // translate to internal action 'complete'
                $action ='complete';
                break;
            case 'complete':
                // complete action triggers a notification (notification from CPP of successful payment completion)
                // note that this could be notifying the CPP of an error condition
                $action ='notify';
                break;
            default:
                $action = '';
                break;
        }
    }

    /**
     * Given the information provided in the request retrieve an OmnipayPayment record
     * When no payment can be found an OmnipayPayment record is returned with a Created status to signal no intent
     * to the PaymentGatewayController
     *
     * > updatePaymentFromRequest called for every request that goes to the PaymentGatewayController.
     * > Can be used to return a Payment object from the request data. Needed for enabling static routes

     * @return false|OmnipayPayment
     */
    public function updatePaymentFromRequest(HTTPRequest $request, $gateway = null)
    {

        try {

            /*
             * Ignore requests for non NSWGOVCPP gateway
              * This can occur if another payment gateway is in use
             */
            if (!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
                return false;
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
                        throw new UnprocessableEntityException("Cancel: no paymentReference supplied");
                    }
                    // get payment based on paymentReference
                    $cppPayment = Payment::getByPaymentReference($paymentReference);
                    if (!$cppPayment || !$cppPayment->isInDB()) {
                        Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching CppPayment record found for this value", "WARNING");
                        throw new UnprocessableEntityException("No payment record found for the the paymentReference provided");
                    }

                    // mark the CPP payment as cancelled
                    Logger::log("Mark cppPayment cancelled", "DEBUG");
                    $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_CANCELLED;
                    $cppPayment->write();

                    $payment = $cppPayment->OmnipayPayment();
                    if ($payment instanceof OmnipayPayment) {
                        // Purchase should remain in a pending state
                        // ServiceResponse = SilverStripe\Omnipay\Service\PurchaseService
                        $payment->Status = 'PendingPurchase';
                        $payment->write();
                        Logger::log("Returning valid OmnipayPayment after cancelled payment", "DEBUG");
                        return $payment;
                    }

                    Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                    throw new \Exception("CPP payment has no Omnipay record", 404);

                    break;

                case 'success':

                    /**
                     * customer sucessfully made the payment
                     * the CPP will redirect the customer back here, after the 'complete' request is made by the CPP gateway
                     */

                     $paymentReference = $request->getVar('paymentReference');
                     if (!$paymentReference) {
                         Logger::log("Successful payment request but no 'paymentReference' query string value provided", "NOTICE");
                         // Bad request
                         throw new \Exception("Success request: no payment reference supplied", 400);
                     }

                     // get payment based on paymentReference
                     $cppPayment = Payment::getByPaymentReference($paymentReference);
                     if (!$cppPayment || !$cppPayment->isInDB()) {
                         Logger::log("Successful payment with paymentReference={$paymentReference} but no matching CppPayment record found for this value", "WARNING");
                         // Payment not found
                         throw new \Exception("Success request: no payment found for paymentReference provided", 404);
                     }

                     // for a successful payment to be received, the payment record must have the values
                     // saved from the 'complete' request
                     if(empty($cppPayment->PaymentCompletionReference)) {
                         Logger::log("Successful payment with PaymentReference={$paymentReference} has no PaymentCompletionReference", "WARNING");
                         throw new \Exception("Success request: no PaymentCompletionReference found for paymentReference provided", 404);
                     }

                     if(empty($cppPayment->PaymentMethod)) {
                         Logger::log("Successful payment with PaymentReference={$paymentReference} has no PaymentMethod", "WARNING");
                         throw new \Exception("Success request: no PaymentMethod found for paymentReference provided", 404);
                     }

                     $payment = $cppPayment->OmnipayPayment();
                     if ($payment instanceof OmnipayPayment) {
                         if($payment->Status != 'Captured') {
                              throw new \Exception("Cannot mark CPP payment as successfully completed when not mark captured, has it been processed by the 'complete' handler?", 409);
                         } else {
                              // mark the captured payment as completed
                              $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_COMPLETED;
                              $cppPayment->write();
                              return $payment;
                         }
                     }

                     Logger::log("Successful payment with PaymentReference={$paymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                     throw new \Exception("Success request: no Omnipay Payment record found for matched CPP payment", 404);

                    break;

                case 'complete':

                    /**
                     * CPP makes the completion call to the configured agency endpoint after
                     * the payment has been successfully made.
                     * The response has a JWT which should be verified with the key shared with the agency.
                     */

                    // get configured public key for decode
                    $parameters = GatewayInfo::getConfigSetting(Payment::CPP_GATEWAY_CODE, 'parameters');
                    $jwtPublicKey = $parameters['jwtPublicKey'] ?? '';
                    // POST body
                    $body = $request->getBody();

                    // JWT: get payment completion information using JWT provided in body
                    $data = PaymentCompletionService::handle($body, $jwtPublicKey);

                    // agencyTransactionId is used to determine the payment
                    if (empty($data['agencyTransactionId'])) {
                        throw new UnprocessableEntityException("no agencyTransactionId in POST request from CPP");
                    }

                    // Get payment based on paymentReference
                    // TODO: also get by paymentReference
                    $cppPayment = Payment::getByAgencyTransactionId($data['agencyTransactionId']);
                    if (!$cppPayment || !$cppPayment->isInDB()) {
                        throw new UnprocessableEntityException("Completion request: could not get CPP payment for Agency txn: {$data['agencyTransactionId']}");
                    }

                    if(!empty($cppPayment->PaymentCompletionReference)) {
                        // CPP payment already completed and has a reference to say so
                        // avoid CPP pinging again by sending a 422
                        throw new UnprocessableEntityException("CPP Payment #{$cppPayment->ID} already marked as completed");
                    }

                    // get the linked payment record and return it
                    $payment = $cppPayment->OmnipayPayment();
                    if (empty($payment)) {
                        throw new UnprocessableEntityException("Completion request: could not get linked OmnipayPayment payment record for CPP payment #{$cppPayment->ID}/{$data['agencyTransactionId']}");
                    }

                    // Test if payment was already captured...
                    if ($payment->Status == 'Captured') {
                        // payment was already completed
                        // avoid CPP pinging again by sending a 422
                        throw new UnprocessableEntityException("Payment already captured");
                    }

                    // save POSTed output to the CPP payment model
                    // status will be completed as the JWT is only sent when the payment
                    // was successfully made
                    $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_COMPLETED;
                    $cppPayment->PaymentReference = $data['paymentReference'] ?? '';
                    $cppPayment->PaymentCompletionReference = $data['paymentCompletionReference'] ?? '';
                    $cppPayment->BankReference = $data['bankReference'] ?? '';
                    $cppPayment->PaymentMethod = $data['paymentMethod'] ?? '';
                    $cppPayment->write();

                    // Success: ensure payment is marked as Captured
                    if ($payment instanceof OmnipayPayment) {
                        // ServiceResponse = SilverStripe\Omnipay\Service\PurchaseService
                        // Triggers a complete() with isNotification true on the ServiceResponse
                        $payment->Status = 'Captured';
                        $payment->write();
                        return $payment;
                    }

                    // Fallback - cannot find a valid Omnipay Payment or error on write
                    throw new UnprocessableEntityException("Completion request: CPP payment #{$cppPayment->ID} has no OmnipayPayment");

                    break;
                default:
                    throw new UnprocessableEntityException("Unknown status '{$status}'");
                    break;
            } // end switch

        } catch (\Exception $e) {
            // on error return a {@link OmnipayPayment}
            Logger::log("CPP gateway extension exception: " . $e->getMessage());
            return $this->getFailedOmnipayPayment($e);
        }

    } // updatePaymentFromRequest

    /**
     * When a payment is not found, return a subclass'd OmnipayPayment to signal an error
     * This is required to trigger the correct response to the CPP gateway
     * {@link PaymentGatewayController::getPaymentFromRequest}
     * {@link PaymentGatewayController::createPaymentResponse}
     * @return OmnipayPayment
     */
    private function getFailedOmnipayPayment(\Exception $e) : OmnipayPayment {
        $payment = OmnipayPayment::create([
            // trigger a payment intent of ServiceFactory::INTENT_PURCHASE
            // ServiceResponse = SilverStripe\Omnipay\Service\PurchaseService
            'Status' => 'PendingPurchase'
        ]);
        $payment->setException($e);
        return $payment;
    }
}
