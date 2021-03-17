<?php
namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Silverstripe\Control\Controller;
use Silverstripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;


/**
 * API controller for CPP
 * @author James
 */
class APIControllerV1 extends Controller {

    private static $allowed_actions = [
        'complete' => true
    ];

    private static $url_segment = '/cpp/api/v1';

    public function Link($action = '') {
        return Controller::join_links($this->config()->get('url_segment'), $action);
    }

    /**
     * Given an incoming POST request, try to complete the payment
     */
    public function index(HTTPRequest $request) {
        return false;
    }

    /**
     * Given an incoming POST request, try to complete the payment
     */
    public function complete(HTTPRequest $request) {

    }
}
