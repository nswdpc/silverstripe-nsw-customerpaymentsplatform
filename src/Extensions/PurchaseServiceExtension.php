<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\CompletePurchaseRequest;
use Omnipay\NSWGOVCPP\CompletePurchaseResponse;
use Omnipay\NSWGOVCPP\PurchaseRequest;
use Omnipay\NSWGOVCPP\PurchaseResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;

/**
 * Provides extension handling for the PurchaseService
 *
 * @author James
 */
class PurchaseServiceExtension extends Extension
{

    private function getGatewayDataValue($key, &$gatewayData) {
        if(isset($gatewayData[ $key ])) {
            return $gatewayData[ $key ];
        } else {
            return null;
        }
    }

    /**
     * onBeforePurchase
     * Actions to run prior to purchase
     */
    public function onBeforePurchase(&$gatewayData) {

        Logger::log( "onBeforePurchase starts");

        $payment = $this->owner->getPayment();
        $cppPayment = Payment::create();
        $id = $cppPayment->write();
        if($cppPayment->isInDB()) {
            Logger::log( "onBeforePurchase assigned payment {$payment->ID}");
            $cppPayment->OmnipayPaymentID = $payment->ID;
        }
        $cppPayment->PayerFirstname = $this->getGatewayDataValue('firstName', $gatewayData);
        $cppPayment->PayerSurname = $this->getGatewayDataValue('lastName', $gatewayData);
        $cppPayment->PayerMiddlenames = $this->getGatewayDataValue('middleNames', $gatewayData);
        $cppPayment->PayerEmail = $this->getGatewayDataValue('email', $gatewayData);
        $cppPayment->PayerPhone = $this->getGatewayDataValue('phone', $gatewayData);
        $cppPayment->PayerReference = $this->getGatewayDataValue('payerReference', $gatewayData);
        $cppPayment->ProductDescription = $this->getGatewayDataValue('productDescription', $gatewayData);
        $cppPayment->AgencyTransactionId = $this->getGatewayDataValue('transactionId', $gatewayData);
        $cppPayment->PaymentReference = '';// do not have one yet
        $cppPayment->PaymentCompletionReference = '';// do not have one yet
        $cppPayment->BankReference = '';// do not have one yet
        $cppPayment->Amount = $this->getGatewayDataValue('amount', $gatewayData);
        $cppPayment->CurrencyCode = $this->getGatewayDataValue('currency', $gatewayData);
        $cppPayment->PaymentStatus = Payment::PAYMENTSTATUS_INITIALISED;
        $cppPayment->write();

        Logger::log( "onBeforePurchase created cppPayment #{$cppPayment->ID}");

        // create the CPP payload
        $gatewayData['payload'] = [
            "productDescription" => "Payment for {$cppPayment->AgencyTransactionId}",
            "amount" => $cppPayment->Amount,
            "customerReference" => $cppPayment->PayerReference,
            "agencyTransactionId" => $cppPayment->AgencyTransactionId,
            "callingSystem" => "NSWDPC CPP Client"
        ];
    }

    /**
     * onAfterPurchase
     * Actions to run after purchase request was created but not sent
     */
    public function onAfterPurchase(PurchaseRequest $request) {
    }

    public function onAfterSendPurchase(PurchaseRequest $request, PurchaseResponse $response) {
        $paymentReference = $response->getPaymentReference();
        $payment = $this->owner->getPayment();
        $cppPayment = Payment::get()->filter(['OmnipayPaymentID' => $payment->ID])->first();
        if(!$cppPayment || !$cppPayment->isInDB()) {
            throw new \Exception("Failed to find CPP payment record for the current payment");
        }
        $cppPayment->PaymentReference = $paymentReference;
        $cppPayment->PaymentStatus = Payment::PAYMENTSTATUS_IN_PROGRESS;
        $cppPayment->write();
    }

    /**
     * onBeforeCompletePurchase
     * Actions to run before purchase completion
     */
    public function onBeforeCompletePurchase(&$gatewayData) {
        $gatewayData['jwt'] = '';
        Logger::log( "onBeforeCompletePurchase starts");
        // retrieve the JWT from the request
        if(Controller::has_curr()) {
            $controller = Controller::curr();
            $request = $controller->getRequest();
            $body = $request->getBody();
            Logger::log( "onBeforeCompletePurchase body=" . $body );
            $decoded =  json_decode($body, true, JSON_THROW_ON_ERROR);
            $token = $decoded['token'] ?? '';
            $gatewayData['jwt'] = $token;
        }
    }

    /**
     * onAfterCompletePurchase
     * Actions to run after purchase completion
     */
    public function onAfterCompletePurchase(CompletePurchaseRequest $request) {
        Logger::log( "onAfterCompletePurchase:" . json_encode($request) );
    }

}
