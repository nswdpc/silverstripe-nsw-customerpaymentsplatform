<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;
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
     * The response has returned and we are marking it as completed
     */
    public function onCaptured(ServiceResponse &$serviceResponse) {

        Logger::log( "onCaptured starts");

        $omnipayResponse = $serviceResponse->getOmnipayResponse();
        $callback = function(CompletePurchaseResponse $completePurchaseResponse) {
            return true;
        };

        /* @var Response */
        $response = $omnipayResponse->complete($callback);
        // upon completion, set an HTTP response based on what happened in complet()
        $httpResponse = HttpResponse::create();
        $httpResponse->setStatusCode( $response->getStatusCode() );

        Logger::log( "onCaptured setting status code " . $response->getStatusCode());

        $serviceResponse->setHttpResponse( $httpResponse );
    }

    /**
     * in CPP, awaiting captured an captured are the same
     */
    public function onAwaitingCaptured(ServiceResponse &$serviceResponse) {

        Logger::log( "onAwaitingCaptured starts");

        return $this->onCaptured($serviceResponse);
    }

}
