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
class Disbursement extends DataObject implements PermissionProvider {

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
