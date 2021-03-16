<?php

namespace NSWDPC\Payments\CPP;

use LeKoala\CmsActions\CustomAction;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a payment attempt
 * @author James
 */
class Payment extends DataObject implements PermissionProvider {

    use PaymentPermissions;

    const PAYMENT_STATUS_REQUESTED = 'REQUESTED';
    const PAYMENT_STATUS_INITIALISED  = 'INITIALISED';
    const PAYMENT_STATUS_IN_PROGRESS  = 'IN_PROGRESS';
    const PAYMENT_STATUS_PAID  = 'PAID';
    const PAYMENT_STATUS_COMPLETED  = 'COMPLETED';
    const PAYMENT_STATUS_VOIDED  = 'VOIDED';
    const PAYMENT_STATUS_REFUND_REQUESTED = 'REFUND_REQUESTED';
    const PAYMENT_STATUS_REFUND_APPLIED = 'REFUND_APPLIED';
    const PAYMENT_STATUS_CANCELLED = 'CANCELLED';

    private static $table_name = 'CppPayment';

    private static $db = [
        // collect payer details
        'PayerFirstname' => 'Varchar',
        'PayerSurname' => 'Varchar',
        'PayerMiddlenames' => 'Varchar',
        'PayerEmail' => 'Email',
        'PayerPhone' => 'Varchar',
        // our transaction id
        'AgencyTransactionId' => 'Varchar',
        // if taking payment on behalf of a sub agency
        'SubAgencyCode' => 'Varchar',
        // payment gateway information
        'PaymentMethod' => 'Varchar',
        'PaymentReference' => 'Varchar',
        'PaymentCompletionReference' => 'Varchar',
        'BankReference' => 'Varchar',
        'Amount' => 'Currency',
        'CurrencyCode' => 'Varchar(3)',
        'Surcharge' => 'Currency',
        'SurchargeSalesTax' => 'Currency',
        // 1 when payment was completed
        'IsComplete' => 'Boolean',
        // payment status
        'PaymentStatus' => 'Varchar'
    ];

    private static $defaults = [
        'CurrencyCode' => 'AUD',
        'Amount' => 0,
        'Surcharge' => 0,
        'SurchargeSalesTax' => 0,
        'PaymentStatus' => ''
    ];

    private static $indexes = [
        'AgencyTransactionId' => true,
        'PaymentMethod' => true,
        'PaymentReference' => true,
        'PaymentCompletionReference' => true,
        'BankReference' => true,
        'Amount' => true,
        'Surcharge' => true,
        'SurchargeSalesTax' => true,
        'IsComplete' => true,
        'PaymentStatus' => true
    ];

    private static $has_one = [
        'Refund' => Refund::class,
    ];

    private static $has_many = [
        'Disbursements' => Disbursement::class,
    ];

    private static $searchable_fields = [
        'AgencyTransactionId' => 'PartialMatchFilter',
        'SubAgencyCode' => 'PartialMatchFilter',
        'PaymentMethod' => 'ExactMatchFilter',
        'PaymentReference' => 'PartialMatchFilter',
        'PaymentCompletionReference' => 'PartialMatchFilter',
        'BankReference' => 'PartialMatchFilter',
        'Amount' => 'WithinRangeFilter',
        'Surcharge' => 'WithinRangeFilter',
        'SurchargeSalesTax' => 'WithinRangeFilter',
        'IsComplete' => 'ExactMatchFilter',
        'RefundReference' => 'PartialMatchFilter',
        'PaymentStatus' => 'ExactMatchFilter'
    ];

    private static $summary_fields = [
        'AgencyTransactionId' => 'Agency Txn Id',
        'SubAgencyCode' => 'Sub agency code',
        'PaymentMethod' => 'Method',
        'PaymentReference' => 'Pmt Ref',
        'PaymentCompletionReference' => 'Pmt Completion Ref',
        'BankReference' => 'Bank ref',
        'Amount' => 'Amount',
        'Surcharge' => 'Surcharge',
        'SurchargeSalesTax' => 'Surcharge Sales Tax',
        'IsComplete.Nice' => 'Complete?',
        'RefundReference' => 'Refund Ref',
        'PaymentStatus' => 'Status'
    ];

    public function getPaymentStatuses() {
        return [
            self::PAYMENT_STATUS_REQUESTED,
            self::PAYMENT_STATUS_INITIALISED,
            self::PAYMENT_STATUS_IN_PROGRESS,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_COMPLETED,
            self::PAYMENT_STATUS_VOIDED,
            self::PAYMENT_STATUS_REFUND_REQUESTED,
            self::PAYMENT_STATUS_REFUND_APPLIED,
            self::PAYMENT_STATUS_CANCELLED
        ];
    }

    public function getPaymentStatus() {
        $value = $this->getField('PaymentStatus');
        $statuses = $this->getPaymentStatuses();
        $key = array_search($value, $statues);
        if($key !== false) {
            return _t(__CLASS__ . '.PAYMENT_STATUS_' . $statuses[$key], $statuses[$key]);
        } else {
            return "";
        }
    }

    /**
     * Refund this payment, must be completed
     * @param mixed $amount if null, refund the entire amount, this is the default
     * @param string $reason the refund reason
     */
    public function doRefund($amount = 0,  $reason  = '') {

    }

    /**
     * Complete this payment
     */
    public function doComplete(CompletePurchaseResponse $response) {
        return true;
    }

    /**
     * Get the status of this payment
     */
    public function doGetStatus() {

    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        $action_get_status = new CustomAction(
            'doGetStatus',
            _t(__CLASS__ . '.GET_STATUS', 'Get status')
        );
        $action_get_status->setButtonIcon(SilverStripeIcons::ICON_SYNC);
        $actions->push($action_get_status);

        $action_refund = new CustomAction(
            'doRefund',
            _t(__CLASS__ . '.REFUND', 'Refund')
        );
        $action_refund->setButtonIcon(SilverStripeIcons::ICON_CANCEL_CIRCLED);
        $action_refund->setConfirmation(_t(__CLASS__ . '.CONIRM_REFUNED', 'Please confirm you wish to refund the entire amount of this payment'));
        $actions->push($action_refund);

        return $actions;
    }

}
