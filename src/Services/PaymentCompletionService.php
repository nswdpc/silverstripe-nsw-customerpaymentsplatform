<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\JWTProcessor;
use Omnipay\NSWGOVCPP\JWTDecodeException;
use Omnipay\NSWGOVCPP\UnprocessableEntityException;
use SilverStripe\Omnipay\GatewayInfo;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encapsulate payment completion handling - decode the JWT and return the appropriate data if successful
 * @author James
 */
class PaymentCompletionService
{

    /**
     * Given an HTTP request body and a JWT public key, attempt to decode the token provided in the body
     * This will throw an exception to be caught in the controller handling. The exception type determines the
     * HTTP response code to be set back to the CPP
     *
     * @param string $body the JSON encoded POST body
     * @param string $jwtPublicKey the public key to be used when attempting decode
     * @return array
     * @throws \Exception|\Omnipay\NSWGOVCPP\JWTDecodeException|\Omnipay\NSWGOVCPP\UnprocessableEntityException
     */
    public static function handle(string $body, string $jwtPublicKey, array $jwtAlgos = ['RS256']) : array {

        if(empty($body)) {
            throw new UnprocessableEntityException("Empty payment completion body");
        }

        if (empty($jwtPublicKey)) {
            throw new JWTDecodeException("No jwtPublicKey provided");
        }

        $decoded = json_decode($body, true, JSON_THROW_ON_ERROR);
        if (!isset($decoded['token'])) {
            throw new UnprocessableEntityException("No JWT in POST request from CPP");
        }

        if (empty($decoded['token'])) {
            throw new UnprocessableEntityException("Empty JWT value in POST request from CPP");
        }

        // Use the gateway to decode and gather data
        $gateway = $this->getGatewayFactory()->create(Payment::CPP_GATEWAY_CODE);
        $parameters = GatewayInfo::getParameters(Payment::CPP_GATEWAY_CODE);
        // proivde the gateway with the token
        $gatewayData['jwt'] = $token;
        if (is_array($parameters)) {
            $gateway->initialize($parameters);
        }

        $completePurchaseRequest = $gateway->completePurchase();
        /* @var CompletePurchaseResponse */
        $completePurchaseResponse = $request->send();
        /* @var Response */
        $response = $response->complete();
        /* @var array */
        $data = $completePurchaseResponse->getData();

        if(!is_array($data)) {
            throw new UnprocessableEntityException("Decoded output is not an array");
        }

        // decoded JWT for payment reference
        return $data;
    }

}
