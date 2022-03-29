<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\Fieldlist;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Controller for PurchaseApprovalGroupPage
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class PurchaseApprovalGroupPageController extends \PageController {

    /**
     * @var array
     */
    private static $allowed_actions = [
        'ApproveMemberForm'
    ];

    /**
     * Common templating method
     */
    public function Form() {
        return $this->ApproveMemberForm();
    }

    /**
     * Return a form (or not , if there are no users to approve)
     */
    public function ApproveMemberForm() {

        $users = $this->data()->RequestingMembers();

        // No users exist
        if($users->count() == 0) {
            return null;
        }

        $hasAccess = [];
        $requestingAccess = [];
        foreach($users as $user) {
            if($user->GroupCode == PurchaseApprovalGroupPage::getPurchaseRequestAccessGroup()) {
                $requestingAccess[ $user->ID ] = $user->Surname . ", " . $user->FirstName . " ({$user->Email})";
            }
            if($user->GroupCode == PurchaseApprovalGroupPage::getHasAccessToPurchaseGroup()) {
                $hasAccess[ $user->ID ] = $user->Surname . ", " . $user->FirstName . " ({$user->Email})";
            }
        }

        $form = Form::create(
            $this,
            'ApproveMemberForm',
            Fieldlist::create(

                DropdownField::create(
                    'Approve',
                    _t(
                        'payments.USERS_TO_APPROVE',
                        'Give access to someone ({count} in list)',
                        [
                            'count' => count($requestingAccess)
                        ]
                    ),
                    $requestingAccess
                )->setEmptyString(
                    _t(
                        'payments.SELECT_A_USER',
                        'Select a user'
                    )
                ),

                DropdownField::create(
                    'Unapprove',
                    _t(
                        'payments.USERS_TO_UNAPPROVE',
                        'Remove access for someone ({count} in list)',
                        [
                            'count' => count($hasAccess)
                        ]
                    ),
                    $hasAccess
                )->setEmptyString(
                    _t(
                        'payments.SELECT_A_USER',
                        'Select a user'
                    )
                )

            ),
            Fieldlist::create(
                FormAction::create(
                    'doChanges',
                    _t(
                        'payments.SAVE',
                        'Save'
                    )
                )
            )
        );
        return $form;
    }

    /**
     * Do the approve action
     */
    public function doChanges($data, $form) {

        try {

            $users = $this->data()->RequestingMembers();

            if(!$users ||$users->count() == 0) {
                throw new ValidationException(
                    _t(
                        'payments.NO_USERS_TO_APPROVE',
                        'There are no users to approve'
                    )
                );
            }

            // Group representing users that have access
            $hasAccessGroupCode = PurchaseApprovalGroupPage::getHasAccessToPurchaseGroup();
            $hasAccessGroup = null;
            if($hasAccessGroupCode) {
                $hasAccessGroup = Group::get()->filter(['Code' => $hasAccessGroupCode])->first();
            }

            if(empty($hasAccessGroup->ID)) {
                throw new \Exception(
                    _t(
                        'payments.NO_APPROVED_ACCESS_GROUP',
                        'There is no approved purchase access group'
                    )
                );
            }

            if($hasAccessGroup->Permissions()->count() > 0) {
                throw new \Exception(
                    _t(
                        'payments.APPROVED_ACCESS_GROUP_HAS_PERMISSIONS',
                        'The approved access group cannot have permissions'
                    )
                );
            }

            // Group representing users that are requesting access
            $requestAccessGroupCode = PurchaseApprovalGroupPage::getPurchaseRequestAccessGroup();
            $requestAccessGroup = null;
            if($requestAccessGroupCode) {
                $requestAccessGroup = Group::get()->filter(['Code' => $requestAccessGroupCode])->first();
            }

            if(empty($requestAccessGroup->ID)) {
                throw new \Exception(
                    _t(
                        'payments.NO_REQUEST_ACCESS_GROUP',
                        'There is no request purchase access group'
                    )
                );
            }

            if($requestAccessGroup->Permissions()->count() > 0) {
                throw new \Exception(
                    _t(
                        'payments.REQUEST_ACCESS_GROUP_HAS_PERMISSIONS',
                        'The request access group cannot have permissions'
                    )
                );
            }

            // A user to approve
            if(!empty($data['Approve'])) {

                // check user is valid
                $toApprove = $users->byId( $data['Approve'] );
                if(empty($toApprove['ID'])) {
                    throw new ValidationException(
                        _t(
                            'payments.USER_TO_APPROVE_NO',
                            'The user selected cannot be approved'
                        )
                    );
                } else {

                    //
                    $member = Member::get()->byId($data['Approve']);

                    // Switch groups
                    if(!empty($hasAccessGroup->ID)) {
                        // add to has access group
                        $member->Groups()->add( $hasAccessGroup );
                    }

                    if(!empty($requestAccessGroup->ID)) {
                        // remove requst access group
                        $member->Groups()->remove( $requestAccessGroup );
                    }

                    $form->sessionMessage(
                        _t(
                            'payments.APPROVED_TO_PURCHASE',
                            'Access was given to {firstname} {surname}',
                            [
                                'firstname' => $member->FirstName,
                                'surname' => $member->Surname
                            ]
                        ),
                        ValidationResult::TYPE_GOOD
                    );

                    try {
                        $this->sendApprovalChangeEmail( $member );
                    } catch (\Exception $e) {
                        Logger::log("Failed to send purchase request approval email to selected member");
                    }

                }

            }

            if(!empty($data['Unapprove'])) {

                $toUnapprove = $users->byId( $data['Unapprove'] );
                if(empty($toUnapprove['ID'])) {
                    throw new ValidationException(
                        _t(
                            'payments.USER_TO_APPROVE_NO',
                            'The user selected cannot have access removed'
                        )
                    );
                } else {

                    $member = Member::get()->byId($data['Unapprove']);
                    if(!empty($hasAccessGroup->ID)) {
                        if(!empty($hasAccessGroup->ID)) {
                            // add to has access group
                            $member->Groups()->add( $requestAccessGroup );
                        }

                        if(!empty($requestAccessGroup->ID)) {
                            // add to has access group
                            $member->Groups()->remove( $hasAccessGroup );
                        }
                    }

                    $form->sessionMessage(
                        _t(
                            'payments.APPROVED_TO_PURCHASE',
                            'Access was removed for {firstname} {surname}',
                            [
                                'firstname' => $member->FirstName,
                                'surname' => $member->Surname
                            ]
                        ),
                        ValidationResult::TYPE_GOOD
                    );

                }

            }

        } catch (ValidationException $e) {
            $form->sessionError(
                $e->getMessage(),
                ValidationResult::TYPE_BAD
            );
        } catch (\Exception $e) {
            Logger::log("Failed to perform request approval. Error=" . $e->getMessage(), "NOTICE");
            $form->sessionError(
                _t(
                    'payments.FAILED_TO_PERFORM_APPROVAL_REQUEST',
                    'Sorry, this request cannot be made at the current time due to a system error. Please try again later.'
                ),
                ValidationResult::TYPE_BAD
            );
        } finally {
            return $this->redirectBack();
        }

    }//end doChanges

    /**
     * Send the approval email - when a member is approved
     */
    private function sendApprovalChangeEmail(Member $member) {
        $siteConfig = SiteConfig::current_site_config();
        $to = [
            $member->Email => "{$member->FirstName} {$member->Surname}"
        ];
        $email = Email::create();
        $email->setTo($to);
        $email->setSubject(
            _t(
                'payments.PURCHASE_APPROVAL_GIVEN',
                'Purchase approval given on {site}',
                [
                    'site' => $siteConfig->Title
                ]
            )
        );

        $page = PurchaseApprovalGroupPage::get()->first();
        if(!empty($page->ID)) {
            $body = $page->ApprovalEmailContent;
            if(!$body) {
                $body = Config::inst()->get( PurchaseApprovalGroupPage::class, 'approval_email_content');
            }
        }

        $email->setBody( $body );

        return $email->send();
    }
}
