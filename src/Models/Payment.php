<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Codem\Utilities\HTML5\TelField;
use LeKoala\CmsActions\CustomAction;
use LeKoala\CmsActions\SilverstripeIcons;
use Omnipay\Omnipay;
use Omnipay\NSWGOVCPP\FetchTransactionRequest;
use Omnipay\NSWGOVCPP\FetchTransactionResponse;
use Omnipay\NSWGOVCPP\FetchTransactionRequestException;
use Omnipay\NSWGOVCPP\Gateway as CppGateway;
use Omnipay\NSWGOVCPP\ParameterStorage;
use SilverShop\HasOneField\HasOneButtonField;
use SilverShop\HasOneField\HasOneAddExistingAutoCompleter;
use SilverShop\HasOneField\GridFieldHasOneUnlinkButton;
use SilverShop\HasOneField\GridFieldHasOneEditButton;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\MoneyField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use SilverStripe\Omnipay\Service\ServiceFactory;
use SilverStripe\Omnipay\Service\ServiceResponse;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a payment
 * @author James
 */
class Payment extends DataObject implements PermissionProvider
{
    use PaymentPermissions;

    /**
     * CPP payment status constants
     *  https://documenter.getpostman.com/view/7222098/SzfCSkTn?version=latest#b3232197-dd91-4698-8c0f-8af5270390b6
     */
    // Agency has requested the payment but customer hasn't initiated the payment
    const CPP_PAYMENTSTATUS_REQUESTED = 'REQUESTED';
    // Agency has requested the payment but customer hasn't initiated the payment
    const CPP_PAYMENTSTATUS_INITIALISED  = 'INITIALISED';
    // Payment in progress
    const CPP_PAYMENTSTATUS_IN_PROGRESS  = 'IN_PROGRESS';
    // Customer has paid
    const CPP_PAYMENTSTATUS_PAID  = 'PAID';
    // Customer has paid and CPP has notified the agency successfully
    const CPP_PAYMENTSTATUS_COMPLETED  = 'COMPLETED';
    // Payment Voided
    const CPP_PAYMENTSTATUS_VOIDED  = 'VOIDED';
    // Refund has been requested and is in progress
    const CPP_PAYMENTSTATUS_REFUND_REQUESTED = 'REFUND_REQUESTED';
    // Refund has been applied
    const CPP_PAYMENTSTATUS_REFUND_APPLIED = 'REFUND_APPLIED';
    // Payment cancelled
    const CPP_PAYMENTSTATUS_CANCELLED = 'CANCELLED';
    // ANY OTHER STATUS - Payment hasn't been successful

    private static $table_name = 'CppPayment';

    private static $default_sort = "Created DESC";

    /**
     * The CPP "callingSystem" string
     * This must be set to the value provided by the CPP during setup
     * @todo move to GatewayInfo config parameter?
     * @var string
     */
    private static $calling_system = '';

    /**
     * User Agent sent with HTTP requests
     * @var string
     */
    private static $user_agent = 'NSWDPC-CPP/0.1';

    /**
     * The gateway code used to reference the {@link Omnipay\NSWGOVCPP\Gateway}
     * @var string
     */
    const CPP_GATEWAY_CODE = 'NSWGOVCPP';

    /**
     * Agency transaction prefix (9 chrs max).
     * @config
     * @var string
     */
    private static $transaction_prefix = 'txn';

    /**
     * CPP only accepts AUD payments
     */
    const CURRENCY_CODE = 'AUD';

    private static $singular_name = 'CPP payment';

    private static $plural_name = 'CPP payments';

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
        'SubAgencyCode' => 'Varchar(255)',
        // payment gateway information
        'PaymentReference' => 'Varchar(255)',//CPP payment reference
        'PaymentCompletionReference' => 'Varchar(255)',
        'BankReference' => 'Varchar(255)',
        'PaymentMethod' => 'Varchar(16)',
        'Amount' => 'Money',
        'Surcharge' => 'Currency',
        'SurchargeSalesTax' => 'Currency',
        // payment status
        'PaymentStatus' => 'Varchar(255)',
        // whether the CPP said this was a duplicate, based on AgencyTransactionId
        'IsDuplicate' => 'Boolean',
        // refund info
        'RefundAmount' => 'Money',
        'RefundReason' => 'Text',
        'RefundDatetime' => 'Datetime',
        'RefundReference' => 'Varchar(255)',
        // reconciliation
        'RecReportDateTime' => 'Datetime',
        'RecPaymentCompletionDate' => 'Datetime',
        'RecAmount' => 'Money',
        'RecAgencySettlementDate' => 'Datetime',
        'RecGLIP' => 'Varchar(255)' // SAP GL Lodgement ID
    ];

    private static $defaults = [
        'AmountCurrency' => 'AUD',
        'AmountAmount' => 0,
        'RefundAmount' => 0,
        'RefundCurrency' => 'AUD',
        'RefundReference' => null,
        'PaymentReference' => null,
        'AgencyTransactionId' => null,
        'Surcharge' => 0,
        'SurchargeSalesTax' => 0,
        'PaymentStatus' => '',
        'IsDuplicate' => 0
    ];

    private static $indexes = [
        'AgencyTransactionId' => [ 'type' => 'unique', 'columns' => ['AgencyTransactionId'] ],
        'PaymentMethod' => true,
        'PaymentReference' => [ 'type' => 'unique', 'columns' => ['PaymentReference'] ],
        'RefundReference' => [ 'type' => 'unique', 'columns' => ['RefundReference'] ],
        'PaymentCompletionReference' => true,
        'BankReference' => true,
        'AmountAmount' => true,
        'RefundAmountAmount' => true,
        'Surcharge' => true,
        'SurchargeSalesTax' => true,
        'PaymentStatus' => true,
        'Created' => true,
        'LastEdited' => true,
        'IsDuplicate' => true
    ];

    private static $has_one = [
        'OmnipayPayment' => OmnipayPayment::class // link to the payment record
    ];

    private static $has_many = [
        'Disbursements' => Disbursement::class,
    ];

    private static $searchable_fields = [
        'ProductDescription' => 'PartialMatchFilter',
        'AgencyTransactionId' => 'PartialMatchFilter',
        'SubAgencyCode' => 'PartialMatchFilter',
        'PaymentMethod' => 'ExactMatchFilter',
        'PaymentReference' => 'PartialMatchFilter',
        'PaymentCompletionReference' => 'PartialMatchFilter',
        'BankReference' => 'PartialMatchFilter',
        'Amount' => 'WithinRangeFilter',
        'Surcharge' => 'WithinRangeFilter',
        'SurchargeSalesTax' => 'WithinRangeFilter',
        'RefundReference' => 'PartialMatchFilter',
        'RefundAmount' => 'PartialMatchFilter',
        'PaymentStatus' => 'ExactMatchFilter'
    ];

    private static $summary_fields = [
        'Created.Nice' => 'Created',
        'AgencyTransactionId' => 'Agency Txn Id',
        'PaymentStatus' => 'Status',
        'PaymentMethod' => 'Method',
        'PaymentReference' => 'Pmt Ref',
        'PaymentCompletionReference' => 'Pmt Completion Ref',
        'BankReference' => 'Bank ref',
        'Amount' => 'Amount',
        'Surcharge' => 'Surcharge',
        'RefundReference' => 'Refund',
        'IsReconciledLabel.Nice' => 'Reconciled?'
    ];

    public function getTitle()
    {
        if (!$this->exists()) {
            return _t(
                __CLASS__ . ".MODEL_NOT_EXIST",
                "New payment"
            );
        } elseif (!$this->AgencyTransactionId) {
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
     * AgencyTransactionId is unique, return the record that matches
     * @throws \Exception
     * @return NSWDPC\Payments\NSWGOVCPP\Agency\Payment
     */
    public static function getByAgencyTransactionId($txnId) : Payment
    {
        if (empty($txnId)) {
            throw  new \Exception("Cannot get a payment with an empty txnId");
        }
        $payment = Payment::get()->filter(['AgencyTransactionId' => $txnId])->first();
        if (!$payment instanceof Payment) {
            throw  new \Exception("Payment not found");
        }
        return $payment;
    }

    /**
     * PaymentReference is unique, return the record that matches
     * @throws \Exception
     * @return NSWDPC\Payments\NSWGOVCPP\Agency\Payment
     */
    public static function getByPaymentReference($paymentReference) : Payment
    {
        if (empty($paymentReference)) {
            throw  new \Exception("Cannot get a payment with an empty refId");
        }
        $payment = Payment::get()->filter(['PaymentReference' => $paymentReference])->first();
        if (!$payment instanceof Payment) {
            throw  new \Exception("Payment not found");
        }
        return $payment;
    }

    /**
     * Create a unique transaction id with a prefix
     * This module generates transaction references in the format prefix-ymd-123-45z-ab1
     * @return string
     */
    public static function createAgencyTransactionId()
    {
        $prefix = substr(Config::inst()->get( Payment::class, 'transaction_prefix'), 0, 9);
        do {
            $parts = str_split(bin2hex(random_bytes(5)), 3);
            if (count($parts) < 3) {
                throw new \RuntimeException('Failed to create parts for agency transaction identifier');
            }
            $date =
            $all = [
                $prefix,
                date('Ymd'),
                $parts[0],
                $parts[1],
                $parts[2]
            ];
            $agencyTransactionId = implode("-", $all);
        } while (self::get()->filter( ['AgencyTransactionId' => $agencyTransactionId ])->exists());
        return $agencyTransactionId;
    }

    /**
     * Validate that the transaction reference matches our pattern
     * e.g prefix-ymd-abc-1ab-xy5
     */
    public static function validateTransactionReference(string $reference) {
        $prefix = preg_quote(Config::inst()->get( Payment::class, 'transaction_prefix'));
        $date = date('Ymd');
        $pattern = "/^{$prefix}\-({$date})\-[a-z0-9]{3}\-[a-z0-9]{3}\-[a-z0-9]{3}$/";
        $result = preg_match($pattern, $reference, $matches);
        return $result == 1;
    }

    /**
     * Set defaults as required
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->RefundAmount->getAmount() && $this->RefundAmount->getAmount() > $this->Amount->getAmount()) {
            throw new ValidationException(
                _t(
                    __CLASS__ . ".INVALID_REFUND_AMOUNT",
                    "The refund amount must not be greater than the payment amount ({paymentAmount})",
                    [
                        'paymentAmount' => $this->Amount
                    ]
                )
            );
        }
        // PayerReference cannot include html tags (CPP customerReference)
        $this->PayerReference = strip_tags($this->PayerReference);
    }

    /**
     * Get payment statuses
     * @return array
     */
    public function getPaymentStatuses() : array
    {
        return [
            self::CPP_PAYMENTSTATUS_REQUESTED,
            self::CPP_PAYMENTSTATUS_INITIALISED,
            self::CPP_PAYMENTSTATUS_IN_PROGRESS,
            self::CPP_PAYMENTSTATUS_PAID,
            self::CPP_PAYMENTSTATUS_COMPLETED,
            self::CPP_PAYMENTSTATUS_VOIDED,
            self::CPP_PAYMENTSTATUS_REFUND_REQUESTED,
            self::CPP_PAYMENTSTATUS_REFUND_APPLIED,
            self::CPP_PAYMENTSTATUS_CANCELLED
        ];
    }

    /**
     * Get a valid payment status from the expected payment statuses
     * @return string
     */
    public function getValidPaymentStatus($status) : string
    {
        $statuses = $this->getPaymentStatuses();
        $key = array_search($status, $statuses);
        if ($key !== false) {
            return $statuses[$key];
        } else {
            return '';
        }
    }

    /**
     * Get the value of the payment status
     * @return string
     */
    public function getPaymentStatusLabel() : string
    {
        $value = $this->getField('PaymentStatus');
        $statuses = $this->getPaymentStatuses();
        $key = array_search($value, $statuses);
        if ($key !== false) {
            return _t(__CLASS__ . '.PAYMENTSTATUS_' . $statuses[$key], $statuses[$key]);
        } else {
            return "";
        }
    }

    /**
     * Refund this payment, must be for a completed payment which meets the CPP business validation rules
     */
    public function doRefund()
    {

        // save and validate
        $this->write();

        // check if can be refunded
        if (!$this->isRefundable(true)) {
            return false;
        }

        $intent = ServiceFactory::INTENT_REFUND;
        $payment = $this->OmnipayPayment();
        $service = ServiceFactory::create()->getService($payment, $intent);
        $data['amount'] = $this->RefundAmount->getAmount();
        /**
         * @var ServiceResponse
         */
        $serviceResponse = $service->initiate($data);
        if ($serviceResponse instanceof ServiceResponse) {
            $service->complete();
        }
        // a valid refund leaves a string RefundReference
        return $this->RefundReference != '';
    }

    /**
     * Get the status of this payment
     */
    public function doGetStatus()
    {
        // check if the payment has a reference from the CPP
        if (!$this->PaymentReference) {
            throw new \Exception(
                _t(
                    __CLASS__ . '.PAYMENT_STATUS_NO_PAYMENTREFERENCE',
                    "This payment has no CPP payment reference value"
                )
            );
        }

        // get all parameters for the NSWGOVCPP gateway
        $parameters =  GatewayInfo::getConfigSetting(Payment::CPP_GATEWAY_CODE, 'parameters');
        ParameterStorage::setAll($parameters);
        $statusUrl = $parameters['statusUrl'] ?? '';
        if (!$statusUrl) {
            throw new \Exception(
                _t(
                    __CLASS__ . '.PAYMENT_STATUS_NO_STATUSURL',
                    "The system is not configured to get a payment status - the CPP status URL is required"
                )
            );
        }
        $gateway = Omnipay::create(Payment::CPP_GATEWAY_CODE);
        // get the transaction via the payment reference
        $fetchTransactionRequest = $gateway->fetchTransaction([
            'paymentReference' => $this->PaymentReference
        ]);
        // send the request, get response
        $fetchTransactionResponse = $fetchTransactionRequest->send();
        // get the paymentStatus
        $paymentStatus = $fetchTransactionResponse->getPaymentStatus();
        // is it valid?
        $cppPaymentStatus = $this->getValidPaymentStatus($paymentStatus);
        if (!$cppPaymentStatus) {
            throw new \Exception(
                _t(
                    __CLASS__ . '.PAYMENT_STATUS_NOT_HANDLED',
                    "The status returned '{paymentStatus}' is not a known CPP status",
                    [
                        'paymentStatus' => $paymentStatus
                    ]
                )
            );
        }
        // store the current one
        $previous = $this->PaymentStatus;
        if ($previous != $cppPaymentStatus) {
            // update to the changed on
            $this->PaymentStatus = $cppPaymentStatus;
            $this->write();
            // allow extension to handle a payment status change e.g notify customer
            $this->extend('onAfterPaymentStatusChange', $previous);
            return _t(
                __CLASS__ . '.PAYMENT_STATUS_CHANGED_TO',
                'The payment status was updated to \'{paymentStatus}\'',
                [
                    'paymentStatus' => $cppPaymentStatus
                ]
            );
        } else {
            return _t(
                __CLASS__ . '.PAYMENT_STATUS_UNCHANGED',
                'The payment status has not changed from \'{paymentStatus}\'',
                [
                    'paymentStatus' => $cppPaymentStatus
                ]
            );
        }
    }

    /**
     * Return whether the payment can be refunded
     * @param bool $considerAmount whether to take the amount saved as the refund amount into account
     * TODO: add further business logic from CPP
     */
    public function isRefundable($considerAmount = false)
    {
        $hasValidRefundAmount = true;
        if ($considerAmount) {
            // valid amounts are > 0 and <= payment amount
            $hasValidRefundAmount = $this->RefundAmount->getAmount() > 0
                && ($this->RefundAmount->getAmount() <= $this->Amount->getAmount());
        }
        return !$this->isRefunded()
            && $hasValidRefundAmount
            && $this->PaymentStatus == self::CPP_PAYMENTSTATUS_COMPLETED;
    }

    /**
     * Return whether the payment was refunded
     * TODO: add further business logic from CPP
     */
    public function isRefunded()
    {
        return $this->RefundReference != ""
            && $this->PaymentStatus == self::CPP_PAYMENTSTATUS_REFUND_APPLIED;
    }

    /**
     * Administration actions for record
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        if ($this->exists()) {

            // if the payment has a CPP reference, allow status checks
            if ($this->PaymentReference) {
                $action_get_status = new CustomAction(
                    'doGetStatus',
                    _t(__CLASS__ . '.GET_STATUS', 'Get status')
                );
                $action_get_status->setButtonIcon(SilverStripeIcons::ICON_SYNC);
                $actions->push($action_get_status);
            }

            // if refundable, provide a refund button
            if ($this->isRefundable(true)) {
                $action_refund = new CustomAction(
                    'doRefund',
                    _t(__CLASS__ . '.REFUND', 'Refund')
                );
                $action_refund->setButtonIcon(SilverStripeIcons::ICON_CANCEL_CIRCLED);
                $action_refund->setConfirmation(
                    _t(
                        __CLASS__ . '.CONIRM_REFUNED',
                        'Please confirm you wish to refund the entire amount of this payment'
                    )
                );
                $action_refund->addExtraClass('btn-warning');
                $actions->push($action_refund);
            }
        }
        return $actions;
    }

    /**
     * Administration management fields for record
     */
    public function getCMSFields()
    {
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

        // Refund tab
        $fields->removeByName([
            'RefundReference',
            'RefundAmount',
            'RefundReason',
            'RefundDatetime',
            'doRefund'
        ]);

        if ($this->isRefunded()) {
            // payment was refunded
            $fields->addFieldsToTab(
                "Root.Refund",
                [
                    CompositeField::create(
                        LiteralField::create(
                            'RefundInformation',
                            "<p class=\"message notice\">"
                            . _t(
                                __CLASS__ . '.REFUND_REFUNDED',
                                'This payment was refunded.'
                            )
                            . "</p>"
                        ),
                        ReadonlyField::create(
                            'RefundAmount',
                            _t(__CLASS__ . '.REFUND_AMOUNT', 'Amount')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_AMOUNT_PLACEHOLDER', 'The amount refunded')
                        ),
                        ReadonlyField::create(
                            'RefundReason',
                            _t(__CLASS__ . '.REFUND_AMOUNT', 'Reason')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_AMOUNT_PLACEHOLDER', 'The amount refunded')
                        ),
                        ReadonlyField::create(
                            'RefundReference',
                            _t(__CLASS__ . '.REFUND_REFERENCE', 'Reference')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_PLACEHOLDER', 'This is the reference provided by the CPP')
                        ),
                        ReadonlyField::create(
                            'RefundDatetime',
                            _t(__CLASS__ . '.REFUND_DATE', 'Completion date & time')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_DATE_PLACEHOLDER', 'When the refund was completed')
                        )
                    )
                ]
            );
        } elseif ($this->isRefundable()) {
            // the payment is refundable, allow someone to enter in the
            $fields->addFieldsToTab(
                "Root.Refund",
                [
                    CompositeField::create(
                        LiteralField::create(
                            'RefundInformation',
                            "<p class=\"message notice\">"
                            . _t(
                                __CLASS__ . '.REFUND_HELP',
                                'To refund this payment, enter an amount up to the payment amount of {paymentAmount}, an optional reason '
                                . 'and then hit the refund button. The record will be saved prior to attempting a refund.',
                                [
                                    'paymentAmount' => $this->Amount
                                ]
                            )
                            . "</p>"
                        ),
                        MoneyField::create(
                            'RefundAmount',
                            _t(__CLASS__ . '.REFUND_AMOUNT', 'Refund amount'),
                            $this->Amount
                        )->setAttribute('required', 'required')
                            ->setDescription(
                                _t(
                                    __CLASS__ . '.REFUND_AMOUNT_HELP',
                                    'The amount to be refunded, must be greater than 0, and less than or equal to the payment amount'
                                )
                            )->setAllowedCurrencies(self::CURRENCY_CODE),
                        TextareaField::create(
                            'RefundReason',
                            _t(__CLASS__ . '.REFUND_REASON', 'Reason (optional)')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_REASON_HELP', 'Provide an optional reason for the refund')
                        ),
                        ReadonlyField::create(
                            'RefundReference',
                            _t(__CLASS__ . '.REFUND_REFERENCE', 'Reference')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_PLACEHOLDER', 'When the refund is completed, a reference will appear here')
                        ),
                        ReadonlyField::create(
                            'RefundDatetime',
                            _t(__CLASS__ . '.REFUND_DATE', 'Completion date & time')
                        )->setDescription(
                            _t(__CLASS__ . '.REFUND_DATE_PLACEHOLDER', 'When the refund is completed, this will display the completion date & time')
                        )
                    )->setTitle('Refund')
                ]
            );
        } else {
            $fields->addFieldsToTab(
                "Root.Refund",
                [
                    CompositeField::create(
                        LiteralField::create(
                            'RefundInformation',
                            '<p class="message warning">'
                            . _t(
                                __CLASS__ . '.NOT_REFUNDABLE_HELP',
                                "This payment is not refundable. "
                                . " A payment must have a status of completed, "
                                . " and not have been previously refunded."
                            )
                            . '</p>'
                        )
                    )
                ]
            );
        }

        $fields->makeFieldReadonly('Surcharge');
        $fields->makeFieldReadonly('SurchargeSalesTax');
        $fields->makeFieldReadonly('PaymentReference');
        $fields->makeFieldReadonly('BankReference');
        $fields->makeFieldReadonly('PaymentStatus');
        $fields->makeFieldReadonly('PaymentMethod');
        $fields->makeFieldReadonly('PaymentCompletionReference');
        $fields->makeFieldReadonly('AgencyTransactionId');
        $fields->makeFieldReadonly('Amount');
        $fields->makeFieldReadonly('ProductDescription');
        $fields->makeFieldReadonly('SubAgencyCode');
        $fields->makeFieldReadonly('IsDuplicate');

        $fields->makeFieldReadonly('RecReportDateTime');
        $fields->makeFieldReadonly('RecPaymentCompletionDate');
        $fields->makeFieldReadonly('RecAmount');
        $fields->makeFieldReadonly('RecAgencySettlementDate');
        $fields->makeFieldReadonly('RecGLIP');

        $fields->addFieldsToTab(
            'Root.Reconciliation',
            [
                LiteralField::create(
                    'ReconciliationInformation',
                    '<p class="message notice">'
                    . _t(
                        __CLASS__ . '.RECONCILIATION_HELP',
                        "When the reconciliation report is run, "
                        . " these fields will become populated "
                        . " if the payment is in the report"
                    )
                    . '</p>'
                ),
                $fields->dataFieldByName('RecReportDateTime'),
                $fields->dataFieldByName('RecPaymentCompletionDate'),
                $fields->dataFieldByName('RecAmount'),
                $fields->dataFieldByName('RecAgencySettlementDate'),
            ]
        );

        $fields->removeByName('OmnipayPaymentID');
        $omnipay_payment_field = HasOneButtonField::create(
            $this,
            "OmnipayPayment"
        );
        $config = $omnipay_payment_field->getConfig();
        $config->removeComponentsByType(HasOneAddExistingAutoCompleter::class);
        $config->removeComponentsByType(GridFieldHasOneUnlinkButton::class);
        $button = $config->getComponentByType(GridFieldHasOneEditButton::class);
        $button->setButtonName('View');

        $fields->addFieldToTab(
            'Root.Main',
            $omnipay_payment_field
        );
        return $fields;
    }

    /**
     * Returns whether the amount matches the reconciliation amount reported via
     * {@link NSWDPC\Payments\NSWGOVCPP\Agency\DailyReportService}
     */
    public function IsReconciled() : bool
    {
        $amount = $this->Amount->getAmount();
        $reconciledAmount = $this->RecAmount->getAmount();
        if ($amount) {
            return $amount == $reconciledAmount;
        }
        return false;
    }

    public function IsReconciledLabel() : DBBoolean
    {
        return DBField::create_field(DBBoolean::class, $this->IsReconciled());
    }
}
