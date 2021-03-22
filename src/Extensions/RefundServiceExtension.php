<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\NSWGOVCPP\RefundRequest;
use Omnipay\NSWGOVCPP\RefundResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Provides extension handling for the RefundService
 *
 * @author James
 */
class RefundServiceExtension extends Extension
{

    /**
     * > onBeforeRefund called just before the refund call is being made to the gateway.
     * > Passes the Gateway-Data (an array) as parameter, which allows you to modify the gateway data prior to being sent.
     */
    public function onBeforeRefund(array &$gatewayData) {
        Logger::log( "onBeforeRefund starts" );

        // the Omnipay Payment record
        $payment = $this->owner->getPayment();
        if(!$payment || !$payment instanceof OmnipayPayment || !$payment->isInDB()) {
            Logger::log( "onBeforeRefund OmnipayPayment is not valid");
            return ;
        }

        if($payment->Gateway != Payment::CPP_GATEWAY_CODE) {
            Logger::log( "onBeforeRefund does not handle gateway: {$payment->Gateway}");
            return ;
        }

        // retrieve the matching CPP payment
        $cppPayment = Payment::get()->filter(['OmnipayPaymentID' => $payment->ID])->first();
        if(!$cppPayment || !$cppPayment->isInDB()) {
            throw new \Exception("Failed to find CPP payment record for the current payment");
        }

        // validate if refundable
        if(!$cppPayment->isRefundable()) {
            throw new \Exception("The CPP payment is not in a refundable state");
        }

        // update the payment status
        $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_REFUND_REQUESTED;
        $cppPayment->write();

        // add the CPP refund request paramaters
        $gatewayData["paymentReference"] = $cppPayment->PaymentReference;
        $gatewayData["refundAmount"] = $cppPayment->RefundAmount->getAmount();
        $gatewayData["refundReason"] = $cppPayment->RefundReason;
    }

    /**
     * > onAfterRefund called just after the Omnipay refund call.
     * > Will pass the Omnipay request object as parameter.
     */
    public function onAfterRefund(AbstractRequest $request) {
        Logger::log( "onAfterRefund starts" );
        if(!$request instanceof RefundRequest) {
            Logger::log( "onAfterRefund does not handle: " . get_class($request));
            return;
        }
    }

    /**
     * > onAfterSendRefund called after send has been called on the Omnipay request object.
     * > You'll get the request as first, and the omnipay response as second parameter.
     */
    public function onAfterSendRefund(AbstractRequest $request, AbstractResponse $response) {
        Logger::log( "onAfterSendRefund: " . get_class($response) );
        if(!$response instanceof RefundResponse) {
            Logger::log( "onAfterSendRefund does not handle: " . get_class($response));
            return;
        }

        // This occurs in the same process as onBeforePurchase - can use the same payment record
        $payment = $this->owner->getPayment();
        if(!$payment || !$payment->isInDB()) {
            throw new \Exception("There is no Omnipay payment record for this purchase");
        }

        // retrieve the matching CPP payment
        $cppPayment = Payment::get()->filter(['OmnipayPaymentID' => $payment->ID])->first();
        if(!$cppPayment || !$cppPayment->isInDB()) {
            throw new \Exception("Failed to find CPP payment record for the current payment");
        }

        // get the refund reference from the RefundResponse
        $refundReference = $response->getRefundReference();
        $dt = new \Datetime();

        // update the CPP payment record
        $cppPayment->RefundReference = $refundReference;
        $cppPayment->RefundDatetime = $dt->format('Y-m-d H:i:s');
        // update payment to the CPP "in progress"
        $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_REFUND_APPLIED;
        $cppPayment->write();

    }

}
