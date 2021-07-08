<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Extension;

/**
 * Provides extension to the ShopAccountForm
 *
 * @author James
 */
class SilvershopShopAccountFormExtension extends Extension
{

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
