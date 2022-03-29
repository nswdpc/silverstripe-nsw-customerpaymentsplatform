<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DB;
use SilverStripe\Security\InheritedPermissions;

/**
 * Use this page to allow signed in members to request access to
 * products that are restricted to the {@link PurchaseApprovalGroupPage::getHasAccessToPurchaseGroup()} group
 *
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class RequestPurchaseAccessPage extends \Page
{

    /**
     * @var array
     */
    private static $db = [
        'RequestEmailContent' => 'HTMLText' // content for email
    ];

    /**
     * @var string claim table names
     */
    private static $table_name = 'RequestPurchaseAccessPage';

    /**
     * Add purchase approval fields to page
     */
    public function getCMSFields() {
        $fields = parent::getCmsFields();
        $fields->addFieldToTab(
            'Root.AccessRequest',
            HTMLEditorField::create(
                "RequestEmailContent",
                _t(
                    'payments/REQUEST_EMAIL_CONTENT_TITLE',
                    'Content for the request access email, sent to request approvers'
                )
            )
        );
        return $fields;
    }

    /**
     * This page can only be accessed by logged-in users only
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $this->CanViewType = InheritedPermissions::LOGGED_IN_USERS;
        $this->ShowInMenus = 0;
        $this->ShowInSearch = 0;
    }

    /**
     * Require default records on build
     */
    public function requireDefaultRecords() {
        $page = RequestPurchaseAccessPage::get()->first();
        if(empty($page->ID)) {
            // create the page on build
            $page = RequestPurchaseAccessPage::create();
            $page->Title = _t(
                'payments.REQUEST_PURCHASE_ACCESS',
                'Request purchase access'
            );
            $page->ParentID = 0;
        }
        $page->write();
        DB::alteration_message("RequestPurchaseAccessPage updated", "changed");
    }


}
