<?php

namespace NSWDPC\Payments\CPP;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
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

    public function providePermissions()
    {
        return Injector::inst()->create( Payment::class )->providePermissions();
    }

    public function canEdit($member = null)
    {
        return Permission::check('CPP_PAYMENT_CANEDIT', 'any', $member);
    }

    public function canCreate($member = null)
    {
        return Permission::check('CPP_PAYMENT_CANCREATE', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return Permission::check('CPP_PAYMENT_CANDELETE', 'any', $member);
    }

    public function canView($member = null)
    {
        return Permission::check('CPP_PAYMENT_CANVIEW', 'any', $member);
    }

}
