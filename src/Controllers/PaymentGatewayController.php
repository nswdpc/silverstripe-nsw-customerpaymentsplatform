<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\NSWGOVCPP\UnprocessableEntityException;
use Omnipay\NSWGOVCPP\NotAllowedException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use SilverStripe\Omnipay\PaymentGatewayController as OmnipayPaymentGatewayController;
use SilverStripe\View\ArrayData;
use Symfony\Component\HttpFoundation\Response;


/**
 * Provide a specific controller to handle payment retrieval and avoid use of extensions
 * This is routed to via the NSWGOVCPP gateway in the URL
 * @author James
 */
class PaymentGatewayController extends OmnipayPaymentGatewayController
{

    private static $allowed_actions = [
        'gateway'
    ];

    private static $url_handlers = [
        'gateway/NSWGOVCPP/$Status' => 'gateway',
    ];

    public function gateway()
    {
        try {
            $routeParams = $this->request->routeParams();
            $routeParams['Gateway'] = Payment::CPP_GATEWAY_CODE;
            $this->request->setRouteParams($routeParams);
            return parent::gateway();
        } catch (\Exception $e) {
            return $this->failOver($e);
        }
    }

    /**
     * Return a HTTPResponse when everything fails
     * @return HTTPResponse
     */
    public function failOver(\Exception $e, $message = '') : HTTPResponse {
        Logger::log(
            'gateway: error=' . $e->getMessage(),
            'WARNING'
        );

        $response = HTTPResponse::create();
        $code = $e->getCode();
        if(!$code || !preg_match("/[0-9]{3}/", $code)) {
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
        }
        if(!$message) {
            $message = _t(
                'NSWGOVCPP.GENERAL_GATEWAY_ERROR',
                'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
            );
        }
        $response->setStatusCode($code);
        $body = ArrayData::create([
            'Title' => 'Error',
            'Code' => $code,
            'Detail' => $message
        ])->renderWith(self::class)->forTemplate();
        $response->setBody($body);
        return $response;
    }

    /**
     * @inheritdoc
     */
    protected function createPaymentResponse($omnipayPayment)
    {

        // No payment found or invalid payment
        if (!$omnipayPayment || !($omnipayPayment instanceof OmnipayPayment)) {
            return $this->failOver(
                new UnprocessableEntityException("createPaymentResponse: invalid Omnipay payment argument"),
                _t(
                    'NSWGOVCPP.HTTP_UNPROCESSABLE_ENTITY',
                    'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
                )
            );
        }

        if(!$omnipayPayment->isInDB()) {
            return $this->failOver(
                new UnprocessableEntityException("createPaymentResponse: Omnipay payment is not in the DB"),
                _t(
                    'NSWGOVCPP.HTTP_UNPROCESSABLE_ENTITY',
                    'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
                )
            );
        }

        // get CPP payment for inspection
        $cppPayment = $omnipayPayment->CppPayment();
        if(!$cppPayment || !($cppPayment instanceof Payment)) {
            return $this->failOver(
                new UnprocessableEntityException("createPaymentResponse: invalid CPP payment argument"),
                _t(
                    'NSWGOVCPP.HTTP_UNPROCESSABLE_ENTITY',
                    'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
                )
            );
        }

        if(!$cppPayment->isInDB()) {
            return $this->failOver(
                new UnprocessableEntityException("createPaymentResponse: CPP payment is not in the DB"),
                _t(
                    'NSWGOVCPP.HTTP_UNPROCESSABLE_ENTITY',
                    'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
                )
            );
        }

        // the CPP payment record holds status
        switch($cppPayment->PaymentStatus) {
            case Payment::CPP_PAYMENTSTATUS_COMPLETED:
                // 'complete' notification
                Logger::log(
                    'createPaymentResponse: OK + complete notification POSTed',
                    'INFO'
                );
                // At this point the JWT + Payment was validated and marked Captured
                $response = HTTPResponse::create();
                $response->setStatusCode(Response::HTTP_OK);// OK for the CPP
                $response->setBody(ArrayData::create([
                    'Title' => 'Success',
                    'Code' => Response::HTTP_OK,
                    'Detail' => _t(
                        'NSWGOVCPP.HTTP_OK',
                        'OK'
                    )
                ])->renderWith(self::class)->forTemplate());
                break;
            case Payment::CPP_PAYMENTSTATUS_CLIENT_ACTION_SUCCESS:
                Logger::log(
                    'createPaymentResponse: OK + success redirect',
                    'INFO'
                );
                // Success handling: captured payment and no notification
                // redirect to the payment success URL
                $response = HTTPResponse::create();
                $response->redirect($omnipayPayment->SuccessUrl, Response::HTTP_MOVED_PERMANENTLY);
                break;
            case Payment::CPP_PAYMENTSTATUS_CANCELLED:
                Logger::log(
                    'createPaymentResponse: OK + cancel redirect',
                    'INFO'
                );
                // 'cancel' handling
                // redirect to the payment failure URL
                $response = HTTPResponse::create();
                $response->redirect($omnipayPayment->FailureUrl, Response::HTTP_MOVED_PERMANENTLY);
                break;
            default:
                // Unhandled status - 422
                return $this->failOver(
                    new UnprocessableEntityException("Error: cannot complete CPP #{$cppPayment->ID}/{$cppPayment->PaymentStatus}/payment #{$omnipayPayment->ID}/status={$omnipayPayment->Status}"),
                    _t(
                        'NSWGOVCPP.HTTP_UNPROCESSABLE_ENTITY',
                        'Sorry, we don\'t know how to handle this request. Future attempts will also fail.'
                    )
                );
                break;
        }
        return $response;
    }

    /**
     * Map CPP status response to Omnipay payment action
     * @inheritdoc
     */
    protected function getPaymentActionFromRequest(HTTPRequest $request, $omnipayPayment)
    {

        // find status: one of cancel|success|complete
        $status = $request->param('Status');

        // verify gateway in use
        $gateway = $request->param('Gateway');
        if (!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            $action = '';
        }

        switch($status) {
            case 'cancel':
                // payer cancelled the payment
                // internal action is also called 'cancel'
                $action = 'cancel';
                break;
            case 'success':
                // successful payment made by payer, the success url is the current URL
                // translate to internal action 'complete'
                $action ='complete';
                break;
            case 'complete':
                // complete action triggers a notification (notification from CPP of successful payment completion)
                // note that this could be notifying the CPP of an error condition
                $action ='notify';
                break;
            default:
                $action = '';
                break;
        }
        return $action;
    }

    /**
     * Get the the {@link \SilverStripe\Omnipay\Model\Payment} record based on the request
     * @return \SilverStripe\Omnipay\Model\Payment|false
     * @inheritdoc
     */
    protected function getPaymentFromRequest(HTTPRequest $request, $gateway = null)
    {

        if(!$gateway || $gateway != Payment::CPP_GATEWAY_CODE) {
            throw new UnprocessableEntityException("Invalid gateway: {$gateway}");
        }

        // act based on Status param in request URL
        $status = strtolower($request->param('Status'));
        switch ($status) {
            case 'cancel':

                if(!$request->isGET()) {
                    throw new NotAllowedException('Cancel: only GET allowed');
                }

                Logger::log("Cancel payment requested", "DEBUG");

                /**
                 * customer has chosen to cancel the payment
                 * the CPP will redirect the customer back here
                 * need to store a status and redirect the customer to their order
                 */
                $paymentReference = $request->getVar('paymentReference');
                if (!$paymentReference) {
                    Logger::log("Cancel payment request but no 'paymentReference' query string value provided", "NOTICE");
                    throw new UnprocessableEntityException("Cancel: no paymentReference supplied");
                }
                // get payment based on paymentReference
                $cppPayment = Payment::getByPaymentReference($paymentReference);
                if (!$cppPayment || !$cppPayment->isInDB()) {
                    Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching CppPayment record found for this value", "WARNING");
                    throw new UnprocessableEntityException("No payment record found for the the paymentReference provided");
                }

                // mark the CPP payment as cancelled
                Logger::log("Mark cppPayment cancelled", "DEBUG");
                $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_CANCELLED;
                $cppPayment->write();

                $omnipayPayment = $cppPayment->OmnipayPayment();
                if ($omnipayPayment instanceof OmnipayPayment) {
                    // Purchase should remain in a pending state
                    // ServiceResponse = SilverStripe\Omnipay\Service\PurchaseService
                    $omnipayPayment->Status = 'PendingPurchase';
                    $omnipayPayment->write();
                    Logger::log("Returning valid OmnipayPayment after cancelled payment", "DEBUG");
                    return $omnipayPayment;
                }

                Logger::log("Cancelled payment with PaymentReference={$paymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                throw new \Exception("CPP payment has no Omnipay record", Response::HTTP_NOT_FOUND);

                break;

            case 'success':

                if(!$request->isGET()) {
                    throw new NotAllowedException('Success: only GET allowed');
                }

                /**
                 * customer sucessfully made the payment
                 * the CPP will redirect the customer back here, after the 'complete' request is made by the CPP gateway
                 */

                 $completionReference = $request->getVar('completionReference');
                 if (!$completionReference) {
                     Logger::log("Successful payment request but no 'completionReference' query string value provided", "NOTICE");
                     // Bad request
                     throw new \Exception("Success request: no payment reference supplied", Response::HTTP_BAD_REQUEST);
                 }

                 // get payment based on paymentReference
                 $cppPayment = Payment::getByPaymentCompletionReference($completionReference);
                 if (!$cppPayment || !$cppPayment->isInDB()) {
                     Logger::log("Successful payment with completionReference={$completionReference} but no matching CppPayment record found for this value", "WARNING");
                     // Payment not found
                     throw new \Exception("Success request: no payment found for completionReference provided", Response::HTTP_NOT_FOUND);
                 }

                 if(empty($cppPayment->PaymentMethod)) {
                     Logger::log("Successful payment with PaymentReference={$paymentReference} has no PaymentMethod", "WARNING");
                     throw new \Exception("Success request: no PaymentMethod found for paymentReference provided", Response::HTTP_NOT_FOUND);
                 }

                 $payment = $cppPayment->OmnipayPayment();
                 if ($payment instanceof OmnipayPayment) {
                     if($payment->Status != 'Captured') {
                          throw new \Exception(
                              "Cannot mark CPP payment as successfully completed when not mark captured, has it been processed by the 'complete' handler?",
                              Response::HTTP_CONFLICT
                          );
                     } else {
                          // Mark the CPP payment as having received the success action request
                          $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_CLIENT_ACTION_SUCCESS;
                          $cppPayment->write();
                          return $payment;
                     }
                 }

                 Logger::log("Successful payment with PaymentReference={$cppPayment->PaymentReference} but no matching OmnipayPayment record found for CppPayment #{$cppPayment}", "WARNING");
                 throw new \Exception("Success request: no Omnipay Payment record found for matched CPP payment", Response::HTTP_NOT_FOUND);

                break;

            case 'complete':

                if(!$request->isPOST()) {
                    throw new NotAllowedException('Complete: only POST allowed');
                }

                /**
                 * CPP makes the completion call to the configured agency endpoint after
                 * the payment has been successfully made.
                 * The response has a JWT which should be verified with the key shared with the agency.
                 */

                // get configured public key for decode
                $parameters = GatewayInfo::getConfigSetting(Payment::CPP_GATEWAY_CODE, 'parameters');
                $jwtPublicKey = $parameters['jwtPublicKey'] ?? '';
                // POST body
                $body = $request->getBody();

                // JWT: get payment completion information using JWT provided in body
                $data = PaymentCompletionService::handle($body, $jwtPublicKey);

                // agencyTransactionId is used to determine the payment
                if (empty($data['agencyTransactionId'])) {
                    throw new UnprocessableEntityException("no agencyTransactionId in POST request from CPP");
                }

                // Get payment based on paymentReference
                // TODO: also get by paymentReference
                $cppPayment = Payment::getByAgencyTransactionId($data['agencyTransactionId']);
                if (!$cppPayment || !$cppPayment->isInDB()) {
                    throw new UnprocessableEntityException("Completion request: could not get CPP payment for Agency txn: {$data['agencyTransactionId']}");
                }

                if(!empty($cppPayment->PaymentCompletionReference)) {
                    // CPP payment already completed and has a reference to say so
                    // avoid CPP pinging again by sending a 422
                    throw new UnprocessableEntityException("CPP Payment #{$cppPayment->ID} already marked as completed");
                }

                // get the linked payment record and return it
                $payment = $cppPayment->OmnipayPayment();
                if (empty($payment)) {
                    throw new UnprocessableEntityException("Completion request: could not get linked OmnipayPayment payment record for CPP payment #{$cppPayment->ID}/{$data['agencyTransactionId']}");
                }

                // Test if payment was already captured...
                if ($payment->Status == 'Captured') {
                    // payment was already completed
                    // avoid CPP pinging again by sending a 422
                    throw new UnprocessableEntityException("Payment already captured");
                }

                // save POSTed output to the CPP payment model
                // status will be completed as the JWT is only sent when the payment
                // was successfully made
                $cppPayment->PaymentStatus = Payment::CPP_PAYMENTSTATUS_COMPLETED;
                $cppPayment->PaymentReference = $data['paymentReference'] ?? '';
                $cppPayment->PaymentCompletionReference = $data['paymentCompletionReference'] ?? '';
                $cppPayment->BankReference = $data['bankReference'] ?? '';
                $cppPayment->PaymentMethod = $data['paymentMethod'] ?? '';
                $cppPayment->write();

                // Success: ensure payment is marked as Captured
                if ($payment instanceof OmnipayPayment) {
                    // ServiceResponse = SilverStripe\Omnipay\Service\PurchaseService
                    // Triggers a complete() with isNotification true on the ServiceResponse
                    $payment->Status = 'Captured';
                    $payment->write();
                    return $payment;
                }

                // Fallback - cannot find a valid Omnipay Payment or error on write
                throw new UnprocessableEntityException("Completion request: CPP payment #{$cppPayment->ID} has no OmnipayPayment");

                break;
            default:
                throw new UnprocessableEntityException("Unknown status '{$status}'");
                break;
        } // end switch

    } // getPaymentFromRequest
}
