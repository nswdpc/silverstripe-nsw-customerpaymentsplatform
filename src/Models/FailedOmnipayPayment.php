<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;

/**
 * Specific subclass of OmnipayPayment returned from the {@link PaymentGatewayControllerExtension}
 * to signal that the payment could not be found or processed
 * A FailedOmnipayPayment contains an exception that signals the HTTP response
 * code to be returned, to allow specific signalling to the CPP gateway
 */
class FailedOmnipayPayment extends OmnipayPayment {

    /**
     * @var \Exception
     */
    private $exception = null;

    /**
     * @param \Exception
     */
    public function setException(\Exception $e) {
        $this->exception = $e;
    }

    /**
     * @return \Exception
     */
    public function getException() : \Exception {
        return $this->exception;
    }

    /**
     * Record cannot be written
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        return false;
    }
}
