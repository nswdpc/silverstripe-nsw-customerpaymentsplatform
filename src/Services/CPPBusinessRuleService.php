<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

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
     * Get a parsed amount based on CPP rules + MoneyPHP parsing
     * Validates the amount value based on CPP rules + MoneyPHP parsing
     *
     * CPP basic rules for amount values:
     * 1. Mandatory but will be ignored for multiple disbursement. Recommend you send 0 (in that case)
     * 2. Should be between 0 and 1,000,000
     * 3. Can take decimal up to 2 place
     *
     * @param string|int|float $amount
     * @return mixed formatted amount from MoneyPHP on success, false on invalid $amount param
     */
    public static function getFormattedAmount($amount, string $currency = 'AUD') {
        if(!is_scalar($amount)) {
            return false;
        }
        if($amount === '') {
            return false;
        }

        $in_range = $amount >= 0 && $amount <= 1e6;
        if(!$in_range) {
            return false;
        }
        try {
            $money = new Money(strval($amount), new Currency($currency));
            $currencies = new ISOCurrencies();
            $moneyFormatter = new DecimalMoneyFormatter($currencies);
            $formatted = $moneyFormatter->format($money);
            return $formatted;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Quickly validate an amount
     * @param string|int|float $amount
     * @return bool
     */
    public static function validateAmount($amount, string $currency = 'AUD') : bool {
        return self::getFormattedAmount($amount) !== false;
    }
}
