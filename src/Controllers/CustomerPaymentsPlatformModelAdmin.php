<?php
namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Silverstripe\Admin\ModelAdmin;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Provide an administration are to view payments
 * @author James
 */
class CustomerPaymentsPlatformModelAdmin extends ModelAdmin {

    private static $url_segment = 'cpp';

    private static $menu_icon_class = 'font-icon-tags';

    private static $managed_models = [
        Payment::class,
        OmnipayPayment::class
    ];

    private static $menu_title = 'CPP';

}
