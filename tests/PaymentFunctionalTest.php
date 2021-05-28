<?php

namespace NSWDPC\Payments\NSWGOVCPP\Tests;

use NSWDPC\Payments\NSWGOVCPP\Agency\Payment;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Dev\FunctionalTest;
use Symfony\Component\Yaml\Parser;

require_once(dirname(__FILE__) . '/support/TestJWTHandling.php');

/**
 * Perform functional tests against the payment gateway controller
 * This requires the nswdpc/silverstripe-nsw-customerpaymentsplatform-fakegateway module installed
 * as a --dev requirement (via composer)
 * nswdpc/silverstripe-nsw-customerpaymentsplatform-fakegateway provides configuration enabled via
 * the USE_FAKE_GATEWAY env variable
 * @author James
 */
class PaymentFunctionalTest extends FunctionalTest {

    use TestJWTHandling;

    /**
     * @inheritdoc
     */
    protected $usesDatabase = true;

    /**
     * @inheritdoc
     * Test needs to inspect response headers
     */
    protected $autoFollowRedirection = false;

    /**
     * @var array
     */
    protected static $fixture_file = [
        __DIR__ . '/support/fixtures/PaymentCompletion.yml'
    ];

    public function setUp() {
        // trigger test configuration
        Environment::setEnv('USE_FAKE_GATEwAY', 1);
        parent::setUp();
    }


    /**
     * Test a payment cancellation:
     * 1. That the payment can be cancelled
     * 2. That the status of the payment is correct
     * 3. That the response is a redirect to the FailureUrl
     */
    public function testPaymentCancel() {

        $omnipayPayment = $this->objFromFixture(OmnipayPayment::class, "paymentToCancel");
        $omnipayPayment->write();
        $cppPayment = $this->objFromFixture(Payment::class, "cppPaymentToCancel");
        $cppPayment->write();

        $path = '/paymentendpoint/gateway/NSWGOVCPP/cancel/';
        $path .= "?paymentReference=" . $cppPayment->PaymentReference;

        $this->assertNotEmpty($path);

        $response = $this->get(
            $path,
            [],
            null,
            null,
            null,
            null // cookies
        );

        $this->assertInstanceOf(HTTPResponse::class, $response);

        $this->assertEquals(301, $response->getStatusCode());

        $location = $response->getHeader("Location");

        $this->assertNotEmpty($location, "Location header value is missing");

        $cppPaymentUpdated = Payment::get()->byId($cppPayment->ID);
        $omnipayPaymentUpdated = OmnipayPayment::get()->byId($omnipayPayment->ID);

        $this->assertEquals(
            $omnipayPaymentUpdated->FailureUrl,
            $location
        );

        $this->assertEquals(
            $cppPaymentUpdated->OmnipayPaymentID,
            $omnipayPaymentUpdated->ID,
            "CPP <-> Omnipay mismatch"
        );

        $this->assertEquals(
            'PendingPurchase',
            $omnipayPaymentUpdated->Status,
            'Omnipay status should be PendingPurchase'
        );

        $this->assertEquals(
            Payment::CPP_GATEWAY_CODE,
            $omnipayPaymentUpdated->Gateway,
            'Omnipay Gateway should be ' . Payment::CPP_GATEWAY_CODE
        );

        $this->assertEquals(
            Payment::CPP_PAYMENTSTATUS_CANCELLED,
            $cppPaymentUpdated->PaymentStatus
        );

        $this->assertNotEmpty(
            $cppPaymentUpdated->PaymentReference
        );

        $this->assertEmpty(
            $cppPaymentUpdated->PaymentCompletionReference
        );

        $this->assertNotEmpty(
            $cppPaymentUpdated->AgencyTransactionId
        );

    }


    /**
     * Test a payment success:
     * 1. That the payment can be marked successful
     * 2. That the status of the payment is correct
     * 3. That the response is a redirect to the SuccessUrl
     */
    public function testPaymentSuccess() {

        $omnipayPayment = $this->objFromFixture(OmnipayPayment::class, "paymentToSuccess");
        $omnipayPayment->write();
        $cppPayment = $this->objFromFixture(Payment::class, "cppPaymentToSuccess");
        $cppPayment->write();

        $path = '/paymentendpoint/gateway/NSWGOVCPP/success/';
        $path .= "?paymentReference=" . $cppPayment->PaymentReference;

        $this->assertNotEmpty($path);

        $response = $this->get(
            $path,
            [],
            null,
            null,
            null,
            null // cookies
        );

        $this->assertInstanceOf(HTTPResponse::class, $response);

        $this->assertEquals(301, $response->getStatusCode());

        $location = $response->getHeader("Location");

        $this->assertNotEmpty($location, "Location header value is missing");

        $cppPaymentUpdated = Payment::get()->byId($cppPayment->ID);
        $omnipayPaymentUpdated = OmnipayPayment::get()->byId($omnipayPayment->ID);

        $this->assertEquals(
            $omnipayPaymentUpdated->SuccessUrl,
            $location
        );

        $this->assertEquals(
            $cppPaymentUpdated->OmnipayPaymentID,
            $omnipayPaymentUpdated->ID,
            "CPP <-> Omnipay mismatch"
        );

        $this->assertEquals(
            'Captured',
            $omnipayPaymentUpdated->Status,
            'Omnipay status should be Captured'
        );

        $this->assertEquals(
            Payment::CPP_GATEWAY_CODE,
            $omnipayPaymentUpdated->Gateway,
            'Omnipay Gateway should be ' . Payment::CPP_GATEWAY_CODE
        );

        $this->assertEquals(
            Payment::CPP_PAYMENTSTATUS_CLIENT_ACTION_SUCCESS,
            $cppPaymentUpdated->PaymentStatus
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentCompletionReference,
            $cppPayment->PaymentCompletionReference,
            "paymentCompletionReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentReference,
            $cppPayment->PaymentReference,
            "paymentReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentMethod,
            $cppPayment->PaymentMethod,
            "paymentReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->AgencyTransactionId,
            $cppPayment->AgencyTransactionId,
            "agencyTransactionId mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->BankReference,
            $cppPayment->BankReference,
            "bankReference mismatch"
        );

    }

    /**
     * Using a JWT payload, create the JWT
     * Test decoding via the paymentendpoint controller
     */
    public function testPaymentCompletion() {

        $omnipayPayment = $this->objFromFixture(OmnipayPayment::class, "paymentToComplete");
        $omnipayPayment->write();
        $cppPayment = $this->objFromFixture(Payment::class, "cppPaymentToComplete");
        $cppPayment->write();

        $testJWT = new TestJWT();
        $jwt = $this->getValidJWT( $testJWT );

        $this->assertNotEmpty($jwt);

        $jwtPublicKey = $testJWT->getVal('jwtPublicKey');
        $this->assertNotEmpty($jwtPublicKey);

        $jwtPayload = json_decode($testJWT->getVal('jwtPayload'), false, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            $jwtPayload->paymentReference,
            $cppPayment->PaymentReference
        );

        // Ensure the correct JWT public key is in configuration
        Config::modify()->merge(
            GatewayInfo::class,
            Payment::CPP_GATEWAY_CODE,
            [
                'parameters' => [
                    'jwtPublicKey' => $jwtPublicKey
                ]
            ]
        );

        $body = json_encode([
            'token' => $jwt
        ]);

        $path = '/paymentendpoint/gateway/NSWGOVCPP/complete/';

        $this->assertNotEmpty($path);

        $response = $this->post(
            $path,
            [],
            null,
            null,
            $body,
            null // cookies
        );

        $this->assertInstanceOf(HTTPResponse::class, $response);

        $this->assertEquals(200, $response->getStatusCode());

        $cppPaymentUpdated = Payment::get()->byId($cppPayment->ID);
        $omnipayPaymentUpdated = OmnipayPayment::get()->byId($omnipayPayment->ID);

        // sanity check on the saved payment records
        $this->assertEquals(
            $cppPaymentUpdated->OmnipayPaymentID,
            $omnipayPaymentUpdated->ID,
            "CPP <-> Omnipay mismatch"
        );

        $this->assertEquals(
            'Captured',
            $omnipayPaymentUpdated->Status,
            'Omnipay status should be Captured'
        );

        $this->assertEquals(
            $omnipayPayment->Identifier,
            $omnipayPaymentUpdated->Identifier,
            'Identifier has been changed'
        );

        $this->assertEquals(
            Payment::CPP_GATEWAY_CODE,
            $omnipayPaymentUpdated->Gateway,
            'Omnipay Gateway should be ' . Payment::CPP_GATEWAY_CODE
        );

        $this->assertEquals(
            Payment::CPP_PAYMENTSTATUS_COMPLETED,
            $cppPaymentUpdated->PaymentStatus,
            "PaymentStatus incorrect"
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentCompletionReference,
            $jwtPayload->paymentCompletionReference,
            "paymentCompletionReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentReference,
            $jwtPayload->paymentReference,
            "paymentReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->PaymentMethod,
            $jwtPayload->paymentMethod,
            "paymentReference mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->AgencyTransactionId,
            $jwtPayload->agencyTransactionId,
            "agencyTransactionId mismatch"
        );

        $this->assertEquals(
            $cppPaymentUpdated->BankReference,
            $jwtPayload->bankReference,
            "bankReference mismatch"
        );



    }
}
