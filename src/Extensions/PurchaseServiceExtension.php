<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\CompletePurchaseRequest;
use Omnipay\NSWGOVCPP\CompletePurchaseResponse;
use Omnipay\NSWGOVCPP\PurchaseRequest;
use Omnipay\NSWGOVCPP\PurchaseResponse;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Provides extension handling for the PurchaseService
 *
 * @author James
 */
class PurchaseServiceExtension extends Extension
{

    /**
     * Get value from gateway data passed in to extensions
     * @return mixed
     */
    private function getGatewayDataValue($key, &$gatewayData)
    {
        if (isset($gatewayData[ $key ])) {
            return $gatewayData[ $key ];
        } else {
            return null;
        }
    }

    /**
     * > onBeforePurchase called just before the purchase call is being made to the gateway.
     * > Passes the Gateway-Data (an array) as parameter, which allows you to modify the gateway data prior to being sent.
     */
    public function onBeforePurchase(array &$gatewayData)
    {
        Logger::log("onBeforePurchase starts");

        // the Omnipay Payment record
        $payment = $this->owner->getPayment();
        if (!$payment || !$payment instanceof OmnipayPayment || !$payment->isInDB()) {
            Logger::log("onBeforePurchase OmnipayPayment is not valid", "WARNING");
            return ;
        }

        if ($payment->Gateway != Payment::CPP_GATEWAY_CODE) {
            // this is OK, just need to ignore payments for other gateways
            Logger::log("onBeforePurchase does not handle gateway: {$payment->Gateway}");
            return ;
        }

        // create a CPP payment record
        $cppPayment = Payment::create();

        /**
         * Set the Agency Transaction ID as that is set by the gateway
         * If you are using Silvershop this will be updated via the generateReference extension method
         * and will modify the original reference created
         * transactionId when Silvershop is installed is generated in {@link \SilverShop\Checkout\OrderProcessor::getGatewayData}
         * The silvershop order process will provide an increment against this value for multiple payment attempts against the same order
         */
        $cppPayment->AgencyTransactionId = Payment::createAgencyTransactionId();
        // override the transactionId provided (e.g Silvershop Order.Reference, if module is installed)
        $gatewayData['transactionId'] = $cppPayment->AgencyTransactionId;
        // validate AgencyTransactionId
        if(!CPPBusinessRuleService::validateAgencyTransactionId($cppPayment->AgencyTransactionId)) {
            Logger::log("Error: new CPP payment has invalid agency transaction ID '{$cppPayment->AgencyTransactionId}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS_ . ".NO_AGENCY_TXN_ID",
                    "The payment request failed due to an internal error"
                )
            );
        }

        // create and validate the payment record
        $id = $cppPayment->write();
        if (!$cppPayment->isInDB()) {
            Logger::log("Error: the CPP payment record could not be saved for txn '{$cppPayment->AgencyTransactionId}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS_ . ".NO_PAYMENT_RECORD_SAVED",
                    "The payment request failed due to an internal error"
                )
            );
        }

        // link to Omnipay record
        $cppPayment->OmnipayPaymentID = $payment->ID;

        /**
         * Payer details
         */
        $cppPayment->PayerFirstname = $this->getGatewayDataValue('firstName', $gatewayData);
        $cppPayment->PayerSurname = $this->getGatewayDataValue('lastName', $gatewayData);
        $cppPayment->PayerMiddlenames = $this->getGatewayDataValue('middleNames', $gatewayData);
        $cppPayment->PayerEmail = $this->getGatewayDataValue('email', $gatewayData);
        $cppPayment->PayerPhone = $this->getGatewayDataValue('phone', $gatewayData);
        $cppPayment->PayerReference = $this->getGatewayDataValue('payerReference', $gatewayData);

        /**
         * Product
         */
        $cppPayment->ProductDescription = $this->getGatewayDataValue('productDescription', $gatewayData);

        /**
         * Set gateway defaults prior to purchase attempt
         */
        $cppPayment->PaymentReference = '';// do not have one yet
        $cppPayment->PaymentCompletionReference = '';// do not have one yet
        $cppPayment->BankReference = '';// do not have one yet
        $cppPayment->PaymentMethod = '';// do not have one yet
        $cppPayment->AmountAmount = $this->getGatewayDataValue('amount', $gatewayData);
        $cppPayment->AmountCurrency = $this->getGatewayDataValue('currency', $gatewayData);
        $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_INITIALISED;

        // Run validation on write()
        $cppPayment->write();

        // check for valid data
        // validate AgencyTransactionId
        if(!CPPBusinessRuleService::validateAgencyTransactionId($cppPayment->AgencyTransactionId)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid agency transction ID '{$cppPayment->AgencyTransactionId}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS_ . ".NO_AGENCY_TXN_ID",
                    "The payment request failed due to an internal error"
                )
            );
        }

        // set product description (todo)
        $productDescription = "Payment for {$cppPayment->AgencyTransactionId}";
        // set amount and validate
        $amount = $cppPayment->Amount->getAmount();
        if(!CPPBusinessRuleService::validateAmount($amount)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid amount '{$amount}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS_ . ".INVALID_AMOUNT",
                    "The payment request failed due to an internal error"
                )
            );
        }

        $callingSystem = $cppPayment->config()->get('calling_system');
        if(!CPPBusinessRuleService::validateCallingSystem($callingSystem)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid callingSystem '{$callingSystem}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS_ . ".INVALID_CALLINGSYSTEM",
                    "The payment request failed due to an internal error"
                )
            );
        }

        /**
         * Create the CPP payload in the gateway data
         * this is POSTed to the CPP as a payment request
         * CPP will send us back a paymentReference
         * TODO: discounts, subAgencyCode, disbursements
         */
        $gatewayData['payload'] = [
            "productDescription" => $productDescription, // mandatory
            "amount" => $amount, // mandatory
            "agencyTransactionId" => $cppPayment->AgencyTransactionId, // mandatory
            "callingSystem" => $callingSystem, // mandatory
            "customerReference" => $cppPayment->PayerReference, // optional
        ];
    }

    /**
     * > onAfterPurchase called just after the Omnipay purchase call.
     * > Will pass the Omnipay request object as parameter.
     */
    public function onAfterPurchase(AbstractRequest $request)
    {
        if (!$request instanceof PurchaseRequest) {
            Logger::log("onAfterPurchase does not handle: " . get_class($request));
            return;
        }
    }

    /**
      * > onAfterSendPurchase called after send has been called on the Omnipay request object.
      * > You'll get the request as first, and the omnipay response as second parameter.
      */
    public function onAfterSendPurchase(AbstractRequest $request, AbstractResponse $response)
    {
        if (!$response instanceof PurchaseResponse) {
            Logger::log("onAfterSendPurchase does not handle: " . get_class($response));
            return;
        }

        // This occurs in the same process as onBeforePurchase - can use the same payment record
        $payment = $this->owner->getPayment();
        if (!$payment || !$payment->isInDB()) {
            throw new \Exception("There is no Omnipay payment record for this purchase");
        }

        // retrieve the matching CPP payment
        $cppPayment = Payment::get()->filter(['OmnipayPaymentID' => $payment->ID])->first();
        if (!$cppPayment || !$cppPayment->isInDB()) {
            throw new \Exception("Failed to find CPP payment record for the current payment");
        }

        // get the payment reference from the PurchaseResponse
        $paymentReference = $response->getPaymentReference();
        $duplicate = $response->isDuplicate();

        // update the CPP payment record
        $cppPayment->PaymentReference = $paymentReference;
        $cppPayment->IsDuplicate = $duplicate ? 1 : 0;
        // update payment to the CPP "in progress"
        $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_IN_PROGRESS;
        $cppPayment->write();
    }

    /**
     * > onBeforeCompletePurchase called just before the completePurchase call is being made to the gateway.
     * > Passes the Gateway-Data (an array) as parameter, which allows you to modify the gateway data prior to being sent.
     */
    public function onBeforeCompletePurchase(array &$gatewayData)
    {
        $payment = $this->owner->getPayment();
        if (!$payment || !$payment instanceof OmnipayPayment || !$payment->isInDB()) {
            Logger::log("onBeforeCompletePurchase OmnipayPayment instance is not valid");
            return ;
        }
        if ($payment->Gateway != Payment::CPP_GATEWAY_CODE) {
            Logger::log("onBeforeCompletePurchase does not handle gateway: {$payment->Gateway}");
            return ;
        }

        // set the JWT as an empty string
        $gatewayData['jwt'] = '';
        Logger::log("onBeforeCompletePurchase starts");
        if (Controller::has_curr()) {
            // retrieve the JWT from the request
            $controller = Controller::curr();
            $request = $controller->getRequest();
            $body = $request->getBody();
            Logger::log("onBeforeCompletePurchase got JWT ");
            $decoded =  json_decode($body, true, JSON_THROW_ON_ERROR);
            $token = $decoded['token'] ?? '';
            // will call setJwt()  on the gateway request
            $gatewayData['jwt'] = $token;
        }
    }

    /**
     * > onAfterCompletePurchase called just after the Omnipay completePurchase call.
     * > Will pass the Omnipay request object as parameter.
     */
    public function onAfterCompletePurchase(AbstractRequest $request)
    {
        if (!$request instanceof CompletePurchaseRequest) {
            // this extension only handles CompletePurchaseRequest instances
            Logger::log("onAfterCompletePurchase does not handle: " . get_class($request));
            return;
        }
    }
}
