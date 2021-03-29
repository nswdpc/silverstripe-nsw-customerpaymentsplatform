<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

/**
 * Perform validation of values based on documented CPP business logic
 * @author James
 */
class CPPBusinessRuleService
{

    /**
     * Validate the agencyTransactionId
     */
    public static function validateAgencyTransactionId($agencyTransactionId) : bool {
        return Payment::validateTransactionReference($agencyTransactionId);
    }

    /**
     * Validate the callingSystem, which cannot be empty
     */
    public static function validateCallingSystem($callingSystem) : bool {
        return $callingSystem !== "";
    }

    /**
     * Validate the amount value based on CPP rules
     * Mandatory but will be ignored for multiple disbursement. Recommend you send 0.
     * Should be between 0 and 1,000,000
     * Can take decimal up to 2 place
     * @return bool
     */
    public static function validateAmount($amount) : bool {
        $valid = is_scalar($amount)
                    && $amount !== ''
                    && !is_null($amount)
                    && is_numeric($amount)
                    && $amount >= 0
                    && $amount <= 1e6;
        return $valid;
    }
}
