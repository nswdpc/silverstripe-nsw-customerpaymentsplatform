<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Extension;

/**
 * Provides extension handling for the RefundService
 *
 * @author James
 */
class RefundServiceExtension extends Extension
{

    /**
     * onBeforeRefund
     * Actions to run prior to refund
     */
    public function onBeforeRefund($gatewayData) {
        Logger::log( "onBeforeRefund:" . json_encode($gatewayData) );
    }

    /**
     * onAfterRefund
     * Actions to run after refund
     */
    public function onAfterRefund($request) {
        Logger::log( "onAfterRefund:" . json_encode($request) );
    }

}
