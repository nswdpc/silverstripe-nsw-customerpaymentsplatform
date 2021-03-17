<?php

namespace NSWDPC\Payments\CPP;

use SilverStripe\Forms\DropdownField;
use Codem\Utilities\HTML5\TelField;
use LeKoala\CmsActions\CustomAction;
use LeKoala\CmsActions\SilverstripeIcons;
use SilverShop\HasOneField\HasOneButtonField;
use SilverShop\HasOneField\HasOneAddExistingAutoCompleter;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a payment
 * @author James
 */
class Payment extends DataObject implements PermissionProvider {

    use PaymentPermissions;

    const PAYMENTSTATUS_REQUESTED = 'REQUESTED';
    const PAYMENTSTATUS_INITIALISED  = 'INITIALISED';
    const PAYMENTSTATUS_IN_PROGRESS  = 'IN_PROGRESS';
    const PAYMENTSTATUS_PAID  = 'PAID';
    const PAYMENTSTATUS_COMPLETED  = 'COMPLETED';
    const PAYMENTSTATUS_VOIDED  = 'VOIDED';
    const PAYMENTSTATUS_REFUND_REQUESTED = 'REFUND_REQUESTED';
    const PAYMENTSTATUS_REFUND_APPLIED = 'REFUND_APPLIED';
    const PAYMENTSTATUS_CANCELLED = 'CANCELLED';

    private static $table_name = 'CppPayment';

    private static $db = [
        // collect payer details
        'PayerFirstname' => 'Varchar(255)',
        'PayerSurname' => 'Varchar(255)',
        'PayerMiddlenames' => 'Varchar(255)',
        'PayerEmail' => 'Varchar(254)',
        'PayerPhone' => 'Varchar(255)',
        'PayerReference' => 'Varchar(250)',
        // payment description
        'ProductDescription' => 'Varchar(250)',
        // our transaction id (50chrs max)
        'AgencyTransactionId' => 'Varchar(50)',
        // if taking payment on behalf of a sub agency
        'SubAgencyCode' => 'Varchar',
        // payment gateway information
        'PaymentReference' => 'Varchar',
        'PaymentCompletionReference' => 'Varchar',
        'BankReference' => 'Varchar',
        'Amount' => 'Currency',
        'CurrencyCode' => 'Varchar(3)',
        'Surcharge' => 'Currency',
        'SurchargeSalesTax' => 'Currency',
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
        'AgencyTransactionId' => [ 'type' => 'unique', 'columns' => ['AgencyTransactionId'] ],
        'PaymentMethod' => true,
        'PaymentReference' => true,
        'PaymentCompletionReference' => true,
        'BankReference' => true,
        'Amount' => true,
        'Surcharge' => true,
        'SurchargeSalesTax' => true,
        'PaymentStatus' => true
    ];

    private static $has_one = [
        'Refund' => Refund::class,
        'PaymentMethod' => PaymentMethod::class
    ];

    private static $has_many = [
        'Disbursements' => Disbursement::class,
    ];

    private static $searchable_fields = [
        'ProductDescription' => 'PartialMatchFilter',
        'AgencyTransactionId' => 'PartialMatchFilter',
        'SubAgencyCode' => 'PartialMatchFilter',
        'PaymentMethod.Code' => 'ExactMatchFilter',
        'PaymentReference' => 'PartialMatchFilter',
        'PaymentCompletionReference' => 'PartialMatchFilter',
        'BankReference' => 'PartialMatchFilter',
        'Amount' => 'WithinRangeFilter',
        'Surcharge' => 'WithinRangeFilter',
        'SurchargeSalesTax' => 'WithinRangeFilter',
        'Refund.Reference' => 'PartialMatchFilter',
        'Refund.Amount' => 'ExactMatchFilter',
        'PaymentStatus' => 'ExactMatchFilter'
    ];

    private static $summary_fields = [
        'AgencyTransactionId' => 'Agency Txn Id',
        'PaymentStatus' => 'Status',
        'SubAgencyCode' => 'Sub agency code',
        'PaymentMethod' => 'Method',
        'PaymentReference' => 'Pmt Ref',
        'PaymentCompletionReference' => 'Pmt Completion Ref',
        'BankReference' => 'Bank ref',
        'Amount' => 'Amount',
        'Surcharge' => 'Surcharge',
        'SurchargeSalesTax' => 'Surcharge Sales Tax',
        'RefundReference' => 'Refund Ref'
    ];

    public function getTitle() {
        if(!$this->exists()) {
            return _t(
                __CLASS__ . ".MODEL_NOT_EXIST",
                "New payment"
            );
        } else if($this->AgencyTransactionId) {
            return _t(
                __CLASS__ . ".MODEL_TITLE",
                "Payment #{id} for unknown agency transaction",
                [
                    'id' => $this->ID
                ]
            );
        } else {
            return _t(
                __CLASS__ . ".MODEL_TITLE",
                "Payment for transaction #{transactionid}",
                [
                    'transactionid' => $this->AgencyTransactionId
                ]
            );
        }
    }

    /**
     * Create a 40 chr random hash for the transaction Id, with a prefix
     */
    public static function createAgencyTransactionId() {
        $prefix = "txn-";
        return $prefix . hash(
            "sha1",
             bin2hex(random_bytes(16))
        );
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if(!$this->exists()) {
            $this->AgencyTransactionId = self::createAgencyTransactionId();
        }
    }

    /**
     * Get payment statuses
     */
    public function getPaymentStatuses() : array {
        return [
            self::PAYMENTSTATUS_REQUESTED,
            self::PAYMENTSTATUS_INITIALISED,
            self::PAYMENTSTATUS_IN_PROGRESS,
            self::PAYMENTSTATUS_PAID,
            self::PAYMENTSTATUS_COMPLETED,
            self::PAYMENTSTATUS_VOIDED,
            self::PAYMENTSTATUS_REFUND_REQUESTED,
            self::PAYMENTSTATUS_REFUND_APPLIED,
            self::PAYMENTSTATUS_CANCELLED
        ];
    }

    /**
     * Get the value of the payment status
     */
    public function getPaymentStatusLabel() : string {
        $value = $this->getField('PaymentStatus');
        $statuses = $this->getPaymentStatuses();
        $key = array_search($value, $statues);
        if($key !== false) {
            return _t(__CLASS__ . '.PAYMENTSTATUS_' . $statuses[$key], $statuses[$key]);
        } else {
            return "";
        }
    }

    /**
     * Refund this payment, must be for a completed payment which meets the CPP business validation rules
     * @param mixed $amount if null, refund the entire amount, this is the default
     * @param string $reason the refund reason
     */
    public function doRefund($amount = 0,  $reason  = '') {
        if(!$this->isRefundable()) {
            return false;
        }
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

    /**
     * Return whether the payment can be refunded
     * TODO: add further business logic from CPP
     */
    public function isRefundable() {
        $completed = $this->PaymentStatus == self::PAYMENTSTATUS_COMPLETED;
        return $completed;
    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        if($this->exists()) {
            $action_get_status = new CustomAction(
                'doGetStatus',
                _t(__CLASS__ . '.GET_STATUS', 'Get status')
            );
            $action_get_status->setButtonIcon(SilverStripeIcons::ICON_SYNC);
            $actions->push($action_get_status);

            if($this->isRefundable()) {
                $action_refund = new CustomAction(
                    'doRefund',
                    _t(__CLASS__ . '.REFUND', 'Refund')
                );
                $action_refund->setButtonIcon(SilverStripeIcons::ICON_CANCEL_CIRCLED);
                $action_refund->setConfirmation(_t(__CLASS__ . '.CONIRM_REFUNED', 'Please confirm you wish to refund the entire amount of this payment'));
                $actions->push($action_refund);
            }
        }
        return $actions;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.Payer',
            [
                TextField::create(
                    'PayerReference',
                    _t(__CLASS__ . ".PAYER_REFERENCE", "Customer-generated payment reference"),
                ),
                TextField::create(
                    'PayerFirstname',
                    _t(__CLASS__ . ".PAYER_FIRSTNAME", "First name"),
                ),
                TextField::create(
                    'PayerMiddlenames',
                    _t(__CLASS__ . ".PAYER_MIDDLENAMES", "Middle names"),
                ),
                TextField::create(
                    'PayerSurname',
                    _t(__CLASS__ . ".PAYER_SURNAME", "Surname"),
                ),
                EmailField::create(
                    'PayerEmail',
                    _t(__CLASS__ . ".PAYER_EMAIL", "E-mail"),
                ),
                TelField::create(
                    'PayerPhone',
                    _t(__CLASS__ . ".PAYER_PHONE", "Phone number"),
                )
            ]
        );

        $fields->removeByName('PaymentMethodID');
        $payment_method_field = HasOneButtonField::create(
            $this,
            "PaymentMethod"
        );
        $config = $payment_method_field->getConfig();
        $config->removeComponentsByType(HasOneAddExistingAutoCompleter::class);

        $fields->addFieldToTab(
            "Root.Main",
            $payment_method_field
        );

        $fields->removeByName('RefundID');
        if($this->isRefundable()) {
            $refund_field = HasOneButtonField::create(
                $this,
                "Refund"
            );
            $config = $refund_field->getConfig();
            $config->removeComponentsByType(HasOneAddExistingAutoCompleter::class);

            $fields->addFieldsToTab(
                "Root.Refund", [
                    LiteralField::create(
                        'RefundInformation',
                        _t(
                            __CLASS__ . '.REFUND_HELP',
                            '<p class="message">To refund this payment, create a refund record with an amount and optional reason. Then use the refund button.</p>'
                        )
                    ),
                    $refund_field
            ]);
        } else {
            $fields->addFieldsToTab(
                "Root.Refund", [
                    LiteralField::create(
                        'RefundInformation',
                        _t(
                            __CLASS__ . '.NOT_REFUNDABLE_HELP',
                            '<p class="message">This payment is not refundable. A payment must have a status of completed, have a refund record and not have been previously refunded.</p>'
                        )
                    )
            ]);
        }

        $fields->makeFieldReadonly('Surcharge');
        $fields->makeFieldReadonly('SurchargeSalesTax');
        $fields->makeFieldReadonly('PaymentReference');
        $fields->makeFieldReadonly('BankReference');
        $fields->makeFieldReadonly('PaymentStatus');
        $fields->makeFieldReadonly('PaymentCompletionReference');
        $fields->makeFieldReadonly('AgencyTransactionId');

        return $fields;
    }

}
