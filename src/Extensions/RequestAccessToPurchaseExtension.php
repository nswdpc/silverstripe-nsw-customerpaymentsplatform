<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;

/**
 * Group extension to handle both requests to access and those who have access
 * Use these groups to determine if the current member can access 'private' products
 * A 'private' product is one that requires access to purchase.
 *
 * @author James
 */
class RequestAccessToPurchaseExtension extends DataExtension
{

    /**
     * Require all default group records for setting up 'private' purchases
     */
    public function requireDefaultRecords() {

        // The 'request access group'
        $requestCode = PurchaseApprovalGroupPage::getPurchaseRequestAccessGroup();
        $requestGroup = Group::get()->filter(['Code' => $requestCode])->first();
        if(empty($requestGroup->ID)) {
            $requestGroup= Group::create([
                'Code' => $requestCode
            ]);
            DB::alteration_message("Request purchase access group created", "created");
        }

        $requestGroup->Title = _t('payments.REQUEST_ACCESS_TO_PURCHASE', 'Purchase access requestors');
        $requestGroup->Description = _t(
            'payments.REQUEST_ACCESS_TO_PURCHASE_DESCRIPTION',
            'A group to hold members who need access to purchase private products.'
        );

        // this group should never have any permissions
        $requestGroup->Permissions()->removeAll();
        $requestGroup->write();

        // The 'Has purchase accesss' group
        $hasCode = PurchaseApprovalGroupPage::getHasAccessToPurchaseGroup();
        $hasGroup = Group::get()->filter(['Code' => $hasCode])->first();
        if(empty($hasGroup->ID)) {
            $hasGroup= Group::create([
                'Code' => $hasCode
            ]);
            DB::alteration_message("Has purchase access group created", "created");
        }

        $hasGroup->Title = _t('payments.HAS_ACCESS_TO_PURCHASE', 'Have purchase access');
        $hasGroup->Description = _t(
            'payments.HAS_ACCESS_TO_PURCHASE_DESCRIPTION',
            'A group to hold members who have access to purchase private products.'
        );

        // this group should never have any permissions
        $hasGroup->Permissions()->removeAll();
        $hasGroup->write();


        // The 'Approve purchase accesss' group
        $approveCode = PurchaseApprovalGroupPage::getApproveAccessToPurchaseGroup();
        $approveGroup = Group::get()->filter(['Code' => $approveCode])->first();
        if(empty($approveGroup->ID)) {
            $approveGroup = Group::create([
                'Code' => $approveCode
            ]);
            DB::alteration_message("Approve purchase access group created", "created");
        }

        $approveGroup->Title = _t('payments.HAS_ACCESS_TO_PURCHASE', 'Purchase access approvers');
        $approveGroup->Description = _t(
            'payments.APPROVE_ACCESS_TO_PURCHASE_DESCRIPTION',
            'A group to hold members who can give other users access to purchase private products.'
        );

        // this group should never have any permissions
        $approveGroup->Permissions()->removeAll();
        $approveGroup->write();
    }

    /**
     * After write, ensures the groups didn't get any permissions
     * These groups should remain permission-less
     */
    public function onAfterWrite() {
        $hasCode = PurchaseApprovalGroupPage::getHasAccessToPurchaseGroup();
        $requestCode = PurchaseApprovalGroupPage::getPurchaseRequestAccessGroup();
        $approveCode = PurchaseApprovalGroupPage::getApproveAccessToPurchaseGroup();
        DB::query(
            "DELETE p "
            . " FROM `Permission` p"
            . " JOIN `Group` g ON g.ID = p.GroupID"
            . " WHERE g.Code IN ('" . implode("','" , Convert::raw2sql([$hasCode, $requestCode, $approveCode])) . "')"
        );
    }
}
