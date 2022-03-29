<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Provides extension handling for the Silvershop Order model
 * ( provided the module is installed )
 *
 * @author James
 */
class SilvershopOrderExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $indexes = [
        // look up orders on reference, needs an index
        'Reference' => true
    ];

    /**
     * @var array
     */
    private static $belongs_to = [
        // This Silvershop order belongs to the OmnipayPayment.OrderID
        // see {@link \SilverShop\Extension\PaymentExtension}
        'Payment' => OmnipayPayment::class . ".Order"
    ];

}
