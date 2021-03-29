<?php

namespace  NSWDPC\Payments\NSWGOVCPP\Tests;

use NSWDPC\Payments\NSWGOVCPP\Agency\Payment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;

class PaymentTest extends SapphireTest {

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

    public function testCppBusinessRuleService() {
        $callingSystem = "";
        $this->assertFalse( CPPBusinessRuleService::validateCallingSystem($callingSystem) );
        $amounts = [
            -12 => false,
            'abc' => false,
            1.00 => true,
            1e6 => true,
            1000001 => false,
            0 => true,
            '' => false,
            '18' => false,
            '12.001' => false
        ];
        foreach($amounts as $amount => $expected) {
            $this->assertEquals( $expected, CPPBusinessRuleService::validateAmount($amount), "The amount '{$amount}' failed the validation test" );
        }
    }
}
