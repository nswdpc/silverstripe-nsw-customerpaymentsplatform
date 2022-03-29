<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\Fieldlist;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;

/**
 * Controller for RequestPurchaseAccessPage
 * Handles accepting and sending purchase access requests
 *
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class RequestPurchaseAccessPageController extends \PageController {

    /**
     * @var array
     */
    private static $allowed_actions = [
        'RequestAccessForm'
    ];

    /**
     * Common templating method
     */
    public function Form() {
        return $this->RequestAccessForm();
    }

    /**
     * Return the form for requesting access
     */
    public function RequestAccessForm() {

        $calloutField = HTMLReadonlyField::create(
            'RequestAccessCallout',
            _t(
                'payments.REQUEST_ACCESS_CALLOUT_TITLE',
                'Help'
            ),
            _t(
                'payments.REQUEST_ACCESS_CALLOUT_CONTENET',
                'Use this form to request access to items available to approved people only. When access is given, these items will be available to purchase'
            )
        );

        if($calloutField->hasMethod('setHintIcon')) {
            $calloutField = $calloutField->setHint('callout')->setHintIcon('info');
        }

        $form = Form::create(
            $this,
            'RequestAccessForm',
            Fieldlist::create(
                $calloutField,
                TextareaField::create(
                    'RequestAccessReason',
                    _t(
                        'payments.REQUEST_ACCESS_REASON',
                        'Please provide a reason for requesting access'
                    )
                )->setAttribute('required','required')
            ),
            Fieldlist::create(
                FormAction::create(
                    'doChanges',
                    _t(
                        'payments.REQUEST_ACCESS',
                        'Request access'
                    )
                )
            ),
            RequiredFields::create(['RequestAccessReason'])
        );

        /*
        if($form->hasMethod('enableSpamProtection')) {
            $form->enableSpamProtection();
        }
        */

        return $form;
    }

    /**
     * Do the approve action
     */
    public function doChanges($data, $form) {

        try {

            // Group representing users that are requesting access
            $requestAccessGroupCode = PurchaseApprovalGroupPage::getPurchaseRequestAccessGroup();
            $requestAccessGroup = null;
            if(!$requestAccessGroupCode) {
                throw new ValidationException(
                    _t(
                        'payments.FAILED_TO_FIND_REQUEST_ACCESS_GROUP',
                        'This request cannot be made at the current time'
                    )
                );
            }

            $requestAccessGroup = Group::get()->filter(['Code' => $requestAccessGroupCode])->first();
            if(empty($requestAccessGroup->Code)) {
                throw new ValidationException(
                    _t(
                        'payments.FAILED_TO_FIND_REQUEST_ACCESS_GROUP',
                        'This request cannot be made at the current time'
                    )
                );
            }

            $permissions = $requestAccessGroup->Permissions();
            if($permissions && $permissions->count() > 0) {
                throw new \Exception("The request access group cannot have permissions");
            }

            $member = Security::getCurrentUser();
            if(empty($member->ID)) {
                throw new ValidationException(
                    _t(
                        'payments.FAILED_TO_FIND_USER',
                        'This request cannot be made at the current time'
                    )
                );
            }

            $member->Groups()->add( $requestAccessGroup );
            try {
                $this->sendApprovalRequestEmail($member, $data);
            } catch (\Exception $e) {
                Logger::log("Failed to send approval request email for access to purchases: " . $e->getMessage(), "NOTICE");
            }

            $message = _t(
                'payments.REQUEST_ACCESS_MADE',
                'Your request has been sent. You will receive a notification when the request is approved'
            );
            $form->sessionMessage(
                $message,
                ValidationResult::TYPE_GOOD
            );

        } catch (ValidationException $e) {

            $form->sessionMessage(
                $e->getMessage(),
                ValidationResult::TYPE_BAD
            );

        } catch (\Exception $e) {
            Logger::log("General error requesting access to purchases: {$e->getMessage()}", "NOTICE");
            $form->sessionMessage(
                _t(
                    'payments.FAILED_TO_PERFORM_REQUEST_ACCESS',
                    'Sorry, this request cannot be made at the current time due to a system error. Please try again later.'
                ),
                ValidationResult::TYPE_BAD
            );

        } finally {
            return $this->redirectBack();
        }

    }//end doChanges

    /**
     * Send the approval request email to the approvers
     */
    private function sendApprovalRequestEmail(Member $fromMember, array $data) {
        $siteConfig = SiteConfig::current_site_config();
        $to = [];

        $approvalPage = PurchaseApprovalGroupPage::get()->first();

        // send to approvers
        $approverGroupCode = PurchaseApprovalGroupPage::getApproveAccessToPurchaseGroup();
        if(!$approverGroupCode) {
            throw new \Exception("There is no approver group code");
        }
        $approverGroup = Group::get()->filter(['Code' => $approverGroupCode])->first();
        if(empty($approverGroup->ID)) {
            throw new \Exception("There is no approver group");
        }

        $approvers = $approverGroup->Members();
        foreach($approvers as $approver) {
            $to = [
                $approver->Email => "{$approver->FirstName} {$approver->Surname}"
            ];
        }

        if(empty($to)) {
            throw new \Exception("There are no purchase request approvers");
        }

        $email = Email::create();
        $email->setTo($to);
        $email->setSubject(
            _t(
                'payments.REQUEST_ACCESS_TO_PURCHASE_PRODUCTS',
                'Purchase request access for {site}',
                [
                    'site' => $siteConfig->Title
                ]
            )
        );

        $page = RequestPurchaseAccessPage::get()->first();
        $body = "";
        if(!empty($page->ID)) {
            $body = $page->RequestEmailContent;
            if(!$body) {
                $body = Config::inst()->get( RequestPurchaseAccessPage::class, 'approval_email_content');
            }
        }

        // per-request details
        $fromMemberContent = ArrayData::create([
            'Content' => $body,
            'ApprovalPage' => $approvalPage,
            'RequestAccessReason' => isset($data['RequestAccessReason']) ? $data['RequestAccessReason'] : '',
            'FromMember' => $fromMember
        ]);

        // Add extra specific request details on
        $emailContent = $fromMemberContent->renderWith('NSWDPC/Payments/NSWGOVCPP/Agency/PurchaseRequestFrom');

        $email->setBody( $emailContent );

        return $email->send();
    }
}
