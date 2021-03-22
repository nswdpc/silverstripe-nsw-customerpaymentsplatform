<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\JWTProcessor;
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
     *
     * > updatePaymentFromRequest called for every request that goes to the PaymentGatewayController.
     * > Can be used to return a Payment object from the request data. Needed for enabling static routes
     */
    public function updatePaymentFromRequest(HTTPRequest $request, $gateway = null) {

        // Ignore requests for non NSWGOVCPP gateway
        if(!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            return;
        }

        // get all parameters for the gateway
        $parameters = GatewayInfo::getConfigSetting($gateway, 'parameters');

        // act based on status
        $status = $request->param('Status');
        switch($status) {
            case 'complete':
                try {

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

                    $leeway = 60;
                    $algos = ["RS256"];
                    $output = JWTProcessor::decode($jwt, $jwtPublicKey, $algos, $leeway);
                    if(empty($output->agencyTransactionId)) {
                        throw new \Exception("no agencyTransactionId in POST request from CPP");
                    }

                    // get payment based on paymentReference
                    $cppPayment = Payment::getByAgencyTransactionId($output->agencyTransactionId);
                    if(!$cppPayment || !$cppPayment->isInDB()) {
                        throw new \Exception("Could not get CPP payment for Agency txn: {$output->agencyTransactionId}");
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

                    // return the linked Omnipay payment record
                    $payment = $cppPayment->OmnipayPayment();
                    // return it
                    if($payment instanceof OmnipayPayment) {
                        return $payment;
                    } else {
                        throw new \Exception("updatePaymentFromRequest: payment #{$cppPayment->ID} has no omnipayPayment");
                    }

                } catch (\Exception $e) {
                    Logger::log( "ERROR on payment complete:" . $e->getMessage(), "NOTICE");
                }
                throw new UnprocessableEntityException("Payment completion could not be processed");
                break;
            default:
                // no operation carried out
                break;
        }
    }
}
