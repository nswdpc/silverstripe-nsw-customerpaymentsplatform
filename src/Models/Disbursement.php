<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a refund for a payment. In CPP a payment can have a single refund.
 * @author James
 */
class Disbursement extends DataObject implements PermissionProvider
{
    use PaymentPermissions;

    private static $table_name = 'CppDisbursement';

    private static $db = [
        'SubAgencyCode' => 'Varchar',
        'Amount' => 'Currency'
    ];

    private static $indexes = [
        'SubAgencyCode' => true,
        'Amount' => true
    ];

    private static $has_one = [
        'Payment' => Payment::class,
    ];
}
