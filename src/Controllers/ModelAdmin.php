<?php
namespace NSWDPC\Payments\CPP;

/**
 * Provide an administration are to view payments
 * @author James
 */
class CppModelAdmin extends ModelAdmin {

    private static $url_segment = 'cpp';

    private static $menu_icon_class = 'font-icon-tags';

    private static $managed_models = [
        Payment::class
    ];

    private static $menu_title = 'CPP';

}
