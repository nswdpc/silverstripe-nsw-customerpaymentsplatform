<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Extension;

/**
 * Provides extension to the ShopAccountForm in SilverShop
 *
 * @author James
 */
class SilvershopShopAccountFormExtension extends Extension
{

    /**
     * Filter allowed fields for use in shop account form
     * @return void
     */
    public function updateShopAccountForm() {
        $allowList = [
            'FirstName',
            'Surname',
            'Email',
        ];

        $this->owner->setFields(
            $this->owner->Fields()->filter(['Name' => $allowList] )
        );

    }
}
