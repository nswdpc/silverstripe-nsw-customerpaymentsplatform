<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\AccessToken;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Permission;

/**
 * Configuration model
 * Stores volatile values such as the current Access Token
 */
class Configuration extends DataObject
{

    private static $table_name = 'CppConfiguration';

    /**
     * @var array
     */
    private static $db = [
        'AccessTokenValue' => 'Varchar(64)',
        'AccessTokenExpires' => 'Int',//seconds
        'AccessTokenExpiry' => 'Int',//timestamp
        'AccessTokenType' => 'Varchar(32)'
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'AccessTokenExpiry' => 0,
        'AccessTokenType' => AccessToken::TYPE_BEARER
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'ExpiryDateTime' => 'Expiry',
        'ExpiryInSeconds' => 'Expiry in'
    ];

    /**
     * @return string
     */
    public function getTitle() {
        return  _t(
            __CLASS__ . ".CONFIGURATION_TITLE",
            "Access token"
        );
    }

    public function ExpiryDateTime() {
        if($this->AccessTokenExpiry > 0) {
            $dt = new \DateTime();
            $dt->setTimestamp($this->AccessTokenExpiry);
            $dt->setTimezone( new \DateTimezone('UTC') );
            return $dt->format('r');
        } else {
            return '';
        }
    }

    public function ExpiryInSeconds() {
        if($this->AccessTokenExpiry > 0) {
            return $this->AccessTokenExpiry - time();
        } else {
            return '';
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'AccessTokenValue',
            'AccessTokenExpiry',
            'AccessTokenExpires' // lifetime
        ]);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function canView($member = null)
    {
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDelete($member = null)
    {
        return !$member || Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
