<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\AccessToken;
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
        if(!BusinessRuleService::validateAgencyTransactionId($cppPayment->AgencyTransactionId)) {
            Logger::log("Error: new CPP payment has invalid agency transaction ID '{$cppPayment->AgencyTransactionId}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS__ . ".NO_AGENCY_TXN_ID",
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
                    __CLASS__ . ".NO_PAYMENT_RECORD_SAVED",
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
        if(!BusinessRuleService::validateAgencyTransactionId($cppPayment->AgencyTransactionId)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid agency transction ID '{$cppPayment->AgencyTransactionId}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS__ . ".NO_AGENCY_TXN_ID",
                    "The payment request failed due to an internal error"
                )
            );
        }

        // set product description (todo)
        $productDescription = "Payment for {$cppPayment->AgencyTransactionId}";
        // set amount and validate
        $amount = $cppPayment->Amount->getAmount();
        if(!BusinessRuleService::validateAmount($amount)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid amount '{$amount}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS__ . ".INVALID_AMOUNT",
                    "The payment request failed due to an internal error"
                )
            );
        }

        $callingSystem = $cppPayment->config()->get('calling_system');
        if(!BusinessRuleService::validateCallingSystem($callingSystem)) {
            Logger::log("Error: CPP payment #{$cppPayment->ID} has invalid callingSystem '{$callingSystem}'", "ERROR");
            throw new \RuntimeException(
                _t(
                    __CLASS__ . ".INVALID_CALLINGSYSTEM",
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
            "callingSystem" => $callingSystem // mandatory
        ];
        if(!empty($cppPayment->PayerReference)) {
            // optional, include when set
            $gatewayData['payload']["customerReference"] = $cppPayment->PayerReference;
        }
    }

    /**
     * This is called after the purchase() method is called, not after an actual purchase
     * The PurchaseService will pass the Omnipay request object as parameter.
     * {@link \SilverStripe\Omnipay\ServicePurchaseService::initiate()}
     */
    public function onAfterPurchase(AbstractRequest $request)
    {
        if (!$request instanceof PurchaseRequest) {
            Logger::log("onAfterPurchase does not handle: " . get_class($request));
            return;
        }

        // Attempt to reuse the stored access token
        if($token = $this->getStoredAccessToken()) {
            // use this token for the request
            $request->setCurrentAccessToken($token);
        } else if($token = $request->retrieveAccessToken()) {
            // store this access token (calls setCurrentAccessToken)
            $this->storeAccessToken($token);
        }

    }

    /**
     * Get the current token
     * if it is valid return.. else return null
     * @return AccessToken|null
     */
    private function getStoredAccessToken() {
        $record = Configuration::get()->first();
        $token = null;
        if($record && $record->exists()) {

            // create a token from the record
            $token = new AccessToken(
                $record->AccessTokenValue,
                intval($record->AccessTokenExpires),
                $record->AccessTokenType,
                intval($record->AccessTokenExpiry)
            );

            // remove all token configurations
            if(!$token->isValid()) {
                // reset token
                $token = null;
                // remove all configurations
                $this->clearAccessTokens();
            }

        }
        return $token;
    }

    /**
     * Remove all configurations
     */
    private function clearAccessTokens() {
        $records = Configuration::get();
        foreach($records as $record) {
            $record->delete();
        }
    }

    /**
     * Store a valid access token
     */
    private function storeAccessToken(AccessToken $token) {
        if($token->isValid()) {
            $this->clearAccessTokens();
            $record = Configuration::create([
                'AccessTokenValue' => $token->getToken(),
                'AccessTokenExpires' => $token->getExpires(),
                'AccessTokenExpiry' => $token->getExpiry(),
                'AccessTokenType' => $token->getType()
            ]);
            $id = $record->write();
            return $record->isInDB();
        } else {
            return false;
        }
    }

    /**
      * > onAfterSendPurchase called after send has been called on the Omnipay request object.
      * > You'll get the request as first, and the omnipay response as second parameter.
      */
    public function onAfterSendPurchase(AbstractRequest $request, AbstractResponse $response)
    {
        if (!$response instanceof PurchaseResponse) {
            return;
        }

        // Store the request token used in the request, if there is one
        if($request instanceof PurchaseRequest) {
            // get the current access token
            $storedToken = $this->getStoredAccessToken();
            $requestToken = $request->getCurrentAccessToken();
            if($requestToken && $storedToken) {
                // replace the token, if the stored one is invalid
                $storedToken->replaceIfExpired($requestToken);
                // store the token
                $this->storeAccessToken($storedToken);
            }
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

}
