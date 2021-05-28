<?php

namespace NSWDPC\Payments\NSWGOVCPP\Tests;

use NSWDPC\Payments\NSWGOVCPP\Agency\Payment;
use NSWDPC\Payments\NSWGOVCPP\Agency\BusinessRuleService;
use NSWDPC\Payments\NSWGOVCPP\Agency\PaymentCompletionService;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;

require_once(dirname(__FILE__) . '/support/TestJWTHandling.php');

class PaymentTest extends SapphireTest {

    use TestJWTHandling;

    /**
     * Test that the agency transaction Id returns a valid value
     */
    public function testAgencyTransactionId() {
        $prefix = preg_quote(Config::inst()->get( Payment::class, 'transaction_prefix'));
        $this->assertNotEmpty($prefix);
        $agencyTransactionId = Payment::createAgencyTransactionId();
        $this->assertNotEmpty($agencyTransactionId);
        $result = Payment::validateTransactionReference($agencyTransactionId);
        $this->assertTrue($result);
        $this->assertTrue(strlen($agencyTransactionId) <= 50);//cpp rule
    }

    /**
     * Validate that the callingSystem param is a valid string
     */
    public function testCppValidateCallingSystem() {
        $callingSystem = "";
        $this->assertFalse( BusinessRuleService::validateCallingSystem($callingSystem) );
    }

    /**
     * Validate that the CPP amount validator parses amounts correctly and within range
     */
    public function testCppValidateAmount() {

        $amounts = [
            [ 'value' => new \stdClass, 'expected' => false ],
            [ 'value' => null, 'expected' => false ],
            [ 'value' => -12, 'expected' => false ],
            [ 'value' => 'abc', 'expected' => false ],
            [ 'value' => 1.00, 'expected' => true ],
            [ 'value' => 1e6, 'expected' => true ],
            [ 'value' => 1000001, 'expected' => false ], // out of range
            [ 'value' => 0, 'expected' => true ],// minimum range
            [ 'value' => -0.01, 'expected' => false ],
            [ 'value' => '', 'expected' => false ],
            [ 'value' => '18', 'expected' => true ],
            [ 'value' => '12.001', 'expected' => false ]
        ];

        $i = 0;
        foreach($amounts as $amount) {
            $type = gettype($amount['value']);
            $found = BusinessRuleService::validateAmount($amount['value']);
            $in = is_scalar($amount['value']) ? $amount['value'] : $type;
            $this->assertEquals(
                $amount['expected'],
                $found, // @var bool
                "The amount [{$in}] of type '{$type}' at position {$i} failed the validation test"
            );
            $i++;
        }
    }

    /**
     * Using a configured JWT payload, create the JWT and test PaymentCompletionService handling
     */
    public function testJwt() {

        $testJWT = new TestJWT();
        $jwt = $this->getValidJWT( $testJWT );

        $body = json_encode([
            'token' => $jwt
        ]);

        $output = PaymentCompletionService::handle(
            $body,
            $testJWT->getVal('jwtPublicKey')
        );

        $this->assertNotEmpty( $output['paymentCompletionReference'] );

    }

}
