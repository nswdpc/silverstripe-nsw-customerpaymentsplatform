<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Omnipay\Service\PurchaseService;
use SilverStripe\Omnipay\Service\ServiceResponse;

/**
 * Specific PurchaseService for the CPP integration to handle completion
 * @author James
 */
class CPPPurchaseService extends PurchaseService
{

    /**
     * Finalise the CPP payment. This handles complete/success/cancel requests
     * Created and called by the PaymentGatewayController.
     * In order to reach this point, the JWT decode has taken place and the controller
     * should redirect/response appropriately
     *
     * CPP completion response documentation:
     * - CPP will retry if there is no response or if the agency send any 5xx http code.
     * - CPP will wait for 90 seconds before retrying
     * - CPP will retry 6 times
     * - CPP will not retry if the endpoint gives a 422 code
     * @inheritdoc
     * @return ServiceResponse
     */
    public function complete($data = array(), $isNotification = false)
    {
        Logger::log('CPPPurchaseService: complete');

        if($this->payment->hasException()) {
            // A payment failure was encountered
            Logger::log('CPPPurchaseService: failed OmnipayPayment has an exception');
            /**
             * Return a response with the relevant error code from the exception
             * Response could be for:
             * CPP Gateway awaiting completion response code (422 or 50x)
             * Payer awaiting a success/cancel response (404)
             */
            $exception = $this->payment->getException();
            // create the appropriate response
            $response = HTTPResponse::create();
            $code = $exception->getCode();
            $response->setStatusCode($code);
            // create a service response with the applicable HTTP code
            $serviceResponse = new ServiceResponse($this->payment, ServiceResponse::SERVICE_ERROR);
            $serviceResponse->setHttpResponse($response);
            Logger::log('CPPPurchaseService: failed OmnipayPayment response code=' . $code);
        } else if ($this->payment->Status == 'Captured') {
            // complete or success
            if($isNotification) {
                Logger::log('CPPPurchaseService: complete notification');
                // complete notification - process the JWT
                // at this point the JWT + Payment was validated and marked Captured
                $response = HTTPResponse::create();
                $response->setStatusCode(200);// OK for the CPP
                // create a service response with the applicable HTTP code
                $serviceResponse = new ServiceResponse($this->payment, ServiceResponse::SERVICE_NOTIFICATION);
                $serviceResponse->setHttpResponse($response);
            } else {
                Logger::log('CPPPurchaseService: success redirect');
                // Success handling: captured payment and no notification
                // redirect to the payment success URL
                $response = HTTPResponse::create();
                $response->redirect($this->payment->SuccessUrl, 301);
                $serviceResponse = new ServiceResponse($this->payment);
                $serviceResponse->setHttpResponse($response);
            }
        } else if($this->payment->Status == 'PendingPurchase') {
            Logger::log('CPPPurchaseService: cancel redirect');
            // 'cancel' handling
            // redirect to the payment failure URL
            $response = HTTPResponse::create();
            $response->redirect($this->payment->FailureUrl, 301);
            $serviceResponse = new ServiceResponse($this->payment, ServiceResponse::SERVICE_CANCELLED);
            $serviceResponse->setHttpResponse($response);
        } else {
            // Unhandled status - 406
            Logger::log("Cannot complete payment #{$this->payment->ID} with status={$this->payment->Status}");
            $response = HTTPResponse::create();
            $response->setStatusCode(406);
            // create a service response with the applicable HTTP code
            $serviceResponse = new ServiceResponse($this->payment, ServiceResponse::SERVICE_ERROR);
            $serviceResponse->setHttpResponse($response);
        }
        return $serviceResponse;
    }

}
