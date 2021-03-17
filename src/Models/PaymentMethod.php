<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents one of the CPP payment method e.g CARD, BPAY, PAYID
 * @author James
 */
class PaymentMethod extends DataObject implements PermissionProvider {

    use PaymentPermissions;

    private static $table_name = 'CppPaymentMethod';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Code' => 'Varchar(255)',
    ];

    private static $belongs_to = [
        'Payment' => Payment::class . '.PaymentMethod',
    ];

}
