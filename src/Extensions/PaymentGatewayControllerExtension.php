<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Firebase\JWT\JWT;
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
     * Called from getPaymentActionFromRequest
     * Using the request data to modify the action value, if required
     */
    public function updatePaymentActionFromRequest($action, $payment, $request) {
        Logger::log( "updatePaymentActionFromRequest:" . $action );
    }

    /**
     * Called from getPaymentFromRequest, using the request data to retrieve the payment it references
     */
    public function updatePaymentFromRequest($request, $gateway) {
        $gateway = "NSWGOVCPP";
        $parameters = GatewayInfo::getConfigSetting($gateway, 'parameters');
        $jwtPublicKey = $parameters['jwtPublicKey'] ?? '';
        $body = $request->getBody();
        $decoded = json_decode($body, true, JSON_THROW_ON_ERROR);
        $jwt = $decoded['token'] ?? '';
        Logger::log( "updatePaymentFromRequest: jwt={$jwt}");
        $output = JWT::decode($jwt, $jwtPublicKey, ["RS256"]);
        if(!empty($output->paymentReference)) {
            $payment = Payment::getByPaymentReference($output->paymentReference);
            if($payment && $payment->isInDB()) {
                // return the linked payment
                $omnipayPayment = $payment->OmnipayPayment();
                if($omnipayPayment instanceof OmnipayPayment) {
                    Logger::log( "updatePaymentFromRequest: omnipayPayment={$omnipayPayment->ID}");
                    return $omnipayPayment;
                } else {
                    Logger::log( "updatePaymentFromRequest: payment #{$payment->ID} has no omnipayPayment" );
                }
            }
        }
        Logger::log( "updatePaymentFromRequest: could not find payment");
        return false;
    }
}
