<?php

namespace NSWDPC\Payments\CPP;

use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

trait PaymentPermissions {

    public function providePermissions()
    {
        return [
            'CPP_PAYMENT_CANEDIT' => [
                'name' => 'Edit a Payment',
                'category' => _t(__CLASS__ . '.PERMISSION_CATEGORY', 'Customer Payments Platform'),
            ],
            'CPP_PAYMENT_CANCREATE' => [
                'name' => 'Create a Payment',
                'category' => _t(__CLASS__ . '.PERMISSION_CATEGORY', 'Customer Payments Platform'),
            ],
            'CPP_PAYMENT_CANDELETE' => [
                'name' => 'Delete a Payment',
                'category' => _t(__CLASS__ . '.PERMISSION_CATEGORY', 'Customer Payments Platform'),
            ],
            'CPP_PAYMENT_CANVIEW' => [
                'name' => 'View a Payment',
                'category' => _t(__CLASS__ . '.PERMISSION_CATEGORY', 'Customer Payments Platform'),
            ]
        ];
    }

    public function canEdit($member = null)
    {
        return Permission::check('CPP_PAYMENT_CANEDIT', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
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
