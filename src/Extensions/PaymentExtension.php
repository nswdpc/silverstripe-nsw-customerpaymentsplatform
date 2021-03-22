<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\CompletePurchaseResponse;
use Omnipay\NSWGOVCPP\RefundResponse;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use SilverStripe\Omnipay\Service\ServiceResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides extension handling to maintain the relation between the CPP Payment
 * model and the Silverstripe Omnipay payment model
 *
 * @author James
 */
class PaymentExtension extends DataExtension
{
    private static $belongs_to = [
        'CppPayment' => Payment::class
    ];

    /**
     * onCaptured - called from markCompleted
     * "onCaptured called when a payment was successfully captured. You'll get the ServiceResponse as parameter."
     */
    public function onCaptured(ServiceResponse &$serviceResponse) {
        Logger::log( "onCaptured starts");
        $omnipayResponse = $serviceResponse->getOmnipayResponse();
        if(!$omnipayResponse instanceof CompletePurchaseResponse) {
            Logger::log( "onCaptured does not handle: " . get_class($omnipayResponse));
            return;
        }
        /**
         * Callback to handle payment completion
         */
        $callback = function(CompletePurchaseResponse $completePurchaseResponse) {
            return true;
        };

        /* @var Response */
        $response = $omnipayResponse->complete($callback);
        // upon completion, set an HTTP response based on what happened in complet()
        $httpResponse = HTTPResponse::create();
        $httpResponse->setStatusCode( $response->getStatusCode() );

        Logger::log( "onCaptured setting status code " . $response->getStatusCode());

        $serviceResponse->setHttpResponse( $httpResponse );
    }

    /**
     * > onAwaitingCaptured called when a purchase completes, but is waiting for an asynchronous notification from the gateway.
     * > You'll get the ServiceResponse as parameter.
     */
    public function onAwaitingCaptured(ServiceResponse &$serviceResponse) {
        Logger::log( "onAwaitingCaptured starts");
        $omnipayResponse = $serviceResponse->getOmnipayResponse();
        if(!$omnipayResponse instanceof CompletePurchaseResponse) {
            Logger::log( "onAwaitingCaptured does not handle: " . get_class($omnipayResponse));
            return;
        }
        return $this->onCaptured($serviceResponse);
    }

    /**
     * > onRefunded called when a payment was successfully refunded.
     * > You'll get the ServiceResponse as parameter.
     */
    public function onRefunded(ServiceResponse &$serviceResponse) {
        Logger::log( "onRefunded starts");
        $omnipayResponse = $serviceResponse->getOmnipayResponse();
        if(!$omnipayResponse instanceof RefundResponse) {
            Logger::log( "onCaptured does not handle: " . get_class($omnipayResponse));
            return;
        }

        /**
         * Send notification to payment holder?
         */

    }

}
