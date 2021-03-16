<?php

namespace NSWDPC\Payments\CPP;

use SilverStripe\Core\Config\Configurable;

/**
 * Configuration model
 */
class Configuration {

    use Configurable;

    /**
     * Provided by the CPP
     * @var string
     */
    private static $client_id = '';

    /**
     * Provided by the CPP
     * @var string
     */
    private static $client_secret = '';

    /**
     * Provided by the CPP
     * @var string
     */
    private static $jwt_secret = '';

    /**
     * Provided by the CPP
     * @var string
     */
    private static $accesstoken_url = '';

    /**
     * Provided by the CPP
     * @var string
     */
    private static $gateway_url = '';

    /**
     * Provided by the CPP
     * @var string
     */
    private static $refund_url = '';

}
