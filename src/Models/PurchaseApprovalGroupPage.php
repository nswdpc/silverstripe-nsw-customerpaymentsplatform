<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

/**
 * Use this page to provide a method to assign members to the "has access to purchase" group
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class PurchaseApprovalGroupPage extends \Page
{

    /**
     * @var array
     */
    private static $db = [
        'ApprovalEmailContent' => 'HTMLText' // content for email
    ];

    /**
     * @var string claim table names
     */
    private static $table_name = 'PurchaseApprovalGroupPage';

    /**
     * Return group code for request access group
     */
    public static function getPurchaseRequestAccessGroup() : string {
        return 'cpp-request-purchase-access';
    }

    /**
     * Group code that has access to purchase items
     */
    public static function getHasAccessToPurchaseGroup() : string {
        return 'cpp-has-purchase-access';
    }

    /**
     * Group code that has access to purchase items
     */
    public static function getApproveAccessToPurchaseGroup() : string {
        return 'cpp-approve-access-to-purchase-access';
    }

    public function getCMSFields() {
        $fields = parent::getCmsFields();
        $fields->addFieldToTab(
            'Root.PurchaseApprovals',
            HTMLEditorField::create(
                "ApprovalEmailContent",
                _t(
                    'payments.APPROVAL_EMAIL_CONTENT_TITLE',
                    'Content for the approval email, sent to users requesting access'
                )
            )
        );
        return $fields;
    }

    /**
     * Ensure permission to view is maintaineds
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $groupCode = self::getApproveAccessToPurchaseGroup();
        $group = null;
        if($groupCode) {
            $group = Group::get()->filter(['Code' => $groupCode])->first();
        }
        $this->CanViewType = 'OnlyTheseUsers';
        if(!empty($group->ID)) {
            $this->ViewerGroups()->add($group);
        }
        $this->ShowInMenus = 0;
        $this->ShowInSearch = 0;
    }

    public function requireDefaultRecords() {
        $page = PurchaseApprovalGroupPage::get()->first();
        if(empty($page->ID)) {
            // create the page on build
            $page = PurchaseApprovalGroupPage::create();
            $page->Title = _t(
                'payments.PURCHASE_REQUEST_APPROVER_PAGE',
                'Purchase access approval'
            );
            $page->ParentID = 0;
        }
        $page->write();
        DB::alteration_message("PurchaseApprovalGroupPage updated", "changed");
    }

    /**
     * Get all members who are either requesting access or have been given access
     * This is done outside the ORM as it is quicker
     */
    public function RequestingMembers() : ArrayList {
        $groupCode = self::getApproveAccessToPurchaseGroup();
        $result = DB::query("SELECT m.ID, m.Email, m.FirstName, m.Surname, g.Code AS GroupCode "
                    . " FROM `Member` m "
                    . " INNER JOIN `Group_Members` gm  ON gm.MemberID = m.ID "
                    . " INNER JOIN `Group` g ON (g.ID = gm.GroupID AND "
                    . " g.Code IN ('"
                    . implode(
                        "','",
                        Convert::raw2sql([
                            self::getPurchaseRequestAccessGroup(),
                            self::getHasAccessToPurchaseGroup()
                        ]))
                    . "'))"
                    . " ORDER BY m.Surname ASC, m.FirstName ASC");

        $list = ArrayList::create();
        if($result) {
            foreach($result as $record) {
                $list->push( $record );
            }
        }
        return $list;
    }


}
