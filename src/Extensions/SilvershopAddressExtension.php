<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Silverstripe\Forms\FormField;
use SilverStripe\ORM\DataExtension;

/**
 * Provides address handling extras  for the SilverShop integration
 *
 * @author James
 */
class SilvershopAddressExtension extends DataExtension
{

    /**
     * Set default selection on new addresses
     */
    public function updateCountryField(FormField $field) {
        $defaultAddress = $this->owner->config()->get('default_country');
        if($defaultAddress) {
            if(!$this->owner->isInDB()) {
                $field->setValue( $defaultAddress );
            }
        }
    }

}
