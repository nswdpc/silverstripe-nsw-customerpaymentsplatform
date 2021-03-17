<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a refund for a payment. In CPP a payment can have a single refund.
 * @author James
 */
class Refund extends DataObject implements PermissionProvider {

    use PaymentPermissions;

    private static $table_name = 'CppRefund';

    private static $db = [
        'Reference' => 'Varchar',
        'Amount' => 'Currency'
    ];

    private static $indexes = [
        'Reference' => true,
        'Amount' => true
    ];

    private static $belongs_to = [
        'Payment' => Payment::class . '.Refund',
    ];

}
