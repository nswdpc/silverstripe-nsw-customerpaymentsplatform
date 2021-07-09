<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\CompletePurchaseResponse;
use Omnipay\NSWGOVCPP\RefundResponse;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use SilverStripe\Omnipay\Service\ServiceResponse;
use Symfony\Component\HttpFoundation\Response;
use SilverShop\HasOneField\HasOneButtonField;
use SilverShop\HasOneField\HasOneAddExistingAutoCompleter;
use SilverShop\HasOneField\GridFieldHasOneUnlinkButton;
use SilverShop\HasOneField\GridFieldHasOneEditButton;
use SilverShop\Model\Order as SilvershopOrder;
use SilverShop\Checkout\OrderProcessor as SilverShopOrderProcessor;

/**
 * Provides extension handling to maintain the relation between the CPP Payment
 * model and the Silverstripe Omnipay payment model
 * It decorates {@link SilverStripe\Omnipay\Model\Payment}
 *
 * @author James
 */
class OmnipayPaymentExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $belongs_to = [
        // this Omnipay Payment record belongs to the CppPayment.OmnipayPaymentID relation
        'CppPayment' => Payment::class . ".OmnipayPayment"
    ];

    /**
     * Update CMS fields based on existence of Silvershop
     */
    public function updateCMSFields(FieldList $fields)
    {
        if(class_exists(SilvershopOrder::class)) {

            $fields->removeByName('OrderID');
            $silvershop_order_field = HasOneButtonField::create(
                $this->owner,
                "Order"
            );
            $config = $silvershop_order_field->getConfig();
            $config->removeComponentsByType(HasOneAddExistingAutoCompleter::class);
            $config->removeComponentsByType(GridFieldHasOneUnlinkButton::class);
            $button = $config->getComponentByType(GridFieldHasOneEditButton::class);
            $button->setButtonName('View');

            $fields->push(
                $silvershop_order_field
            );

        }
    }

    /**
     * > onRefunded called when a payment was successfully refunded.
     * > You'll get the ServiceResponse as parameter.
     * This is fired *after* onAfterSendRefund
     * @todo notify account holder of refund and maybe admin groups
     */
    public function onRefunded(ServiceResponse &$serviceResponse)
    {
        Logger::log("onRefunded starts");
        $omnipayResponse = $serviceResponse->getOmnipayResponse();
        if (!$omnipayResponse instanceof RefundResponse) {
            Logger::log("onRefunded does not handle: " . get_class($omnipayResponse));
            return;
        }

        if (!$this->owner->isInDB()) {
            throw new \Exception("There is no Omnipay payment record for this purchase");
        }

        // check the opayment has a valid status
        if (!$this->owner->Status == 'Refunded') {
            throw new \Exception(
                "Refund cannot be completed for the Omnipay payment record #{$this->owner->ID}"
                . " as its Status={$this->owner->Status}, it must be 'Refunded'"
            );
        }

        // retrieve the matching CPP payment record
        $cppPayment = Payment::get()->filter(['OmnipayPaymentID' => $this->owner->ID])->first();
        if (!$cppPayment || !$cppPayment->isInDB()) {
            throw new \Exception("Failed to find CPP payment record for the current Omnipay payment");
        }

        // Get and validated the CPP refund reference from the RefundResponse
        $refundReference = $omnipayResponse->getRefundReference();
        if (empty($refundReference)) {
            throw new \Exception("The refund reference expected from the CPP gateway was empty");
        }

        // update the CPP payment record
        $cppPayment->RefundReference = $refundReference;
        // ensure the refund date/time is recorded
        $dt = new \Datetime();
        $cppPayment->RefundDatetime = $dt->format('Y-m-d H:i:s');
        // update the status
        $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_REFUND_APPLIED;
        $cppPayment->write();
    }


    /**
     * Place an order for this OmnipayPayment, which occurs just prior to hand-off to gateway
     * @return null
     * @throws \RunTimeException
     */
    public function placeOrderForPayment() {

        Logger::log("placeOrderForPayment OmnipayPayment.Status= " . $this->owner->Status);

        // payment must exist
        if(!$this->owner->isInDB()) {
            throw new \RunTimeException("Cannot place order on a OmnipayPayment that does not exist");
        }

        //
        if($this->owner->Status != 'Created') {
            throw new \RunTimeException("Cannot place order on OmnipayPayment={$this->owner->ID} when status={$this->owner->Status}");
        }

        if(class_exists(SilverShopOrder::class)) {
            /**
             * Place a Silvershop Order created by its OrderProcessor
             * @var SilverShopOrder $order
             */
             $order = SilverShopOrder::get()->byID($this->owner->OrderID);
             if ($order && $order->exists()) {
                 SilverShopOrderProcessor::create($order)->placeOrder();
                 Logger::log("Placed SilverShopOrder #{$order->ID} for OmnipayPayment={$this->owner->ID}");
             }
        } else {
            $this->owner->extend('onPlaceOrderForPayment');
        }
    }

    /**
     * Complete payment for order, which occurs when the Omnipay Payment is marked as captured
     * @throws \RunTimeException
     */
    public function completePaymentForOrder() {

        // payment must exist
        if(!$this->owner->isInDB()) {
            throw new \RunTimeException("Cannot complete OmnipayPayment for an order - OmnipayPayment does not exist");
        }

        if($this->owner->Status != 'Captured') {
            throw new \RunTimeException("Cannot complete OmnipayPayment={$this->owner->ID} for an order when status={$this->owner->Status}");
        }

        if(class_exists(SilverShopOrder::class)) {
            /**
             * @var SilverShopOrder $order
             */
            $order = SilverShopOrder::get()->byID($this->owner->OrderID);
            if ($order && $order->exists()) {
                SilverShopOrderProcessor::create($order)->completePayment();
                Logger::log("Completed payment for SilverShopOrder #{$order->ID} for OmnipayPayment {$this->owner->ID}");
            }
        } else {
            $this->owner->extend('onCompletePaymentForOrder');
        }
    }
}
