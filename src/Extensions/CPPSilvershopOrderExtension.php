<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;

/**
 * Provides extension handling for the Silvershop Order model
 * ( provided the module is installed )
 *
 * @author James
 */
class CPPSilvershopOrderExtension extends DataExtension
{
    private static $indexes = [
        'Reference' => true
    ];
}
