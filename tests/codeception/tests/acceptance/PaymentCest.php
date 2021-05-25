<?php namespace NSWDPC\Payments\NSWGOVCPP\Tests;
use NSWDPC\Payments\NSWGOVCPP\Tests\AcceptanceTester;

class PaymentCest
{

    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * Add to cart -> Checkout -> Payment invalid/valid card.
     */
    public function paymentTest(AcceptanceTester $I)
    {

        // Store current datetime
        $dt = new \Datetime();
        $dt_formatted = $dt->format('r');

        // validate values
        $url = $_ENV['CODECEPTION_WEBSITE_URL'];
        $I->assertNotEmpty($url, "CODECEPTION_WEBSITE_URL not configured in your .env file - please provide a URL");

        $path = $_ENV['CODECEPTION_START_PATH'];
        $I->assertNotEmpty($path, "CODECEPTION_START_PATH not configured in your .env file - please provide a path to the form page");

        $path = rtrim($path, "/") . "/";//ensure a correct trailiing slash

        // validate landing page
        $I->amOnUrl($url);
        $I->amOnPage($path);
        $I->seeElementInDOM('#Products');
        $I->see('Add to Cart');

        $I->makeScreenshot('001_products_listing');

        // click 'Add to cart'
        $I->click('#Products .add > a');

        // redirect back to page after add to cart
        $I->seeCurrentUrlEquals($path);

        // TODO: verify string value
        $I->see("There is 1 item in your cart");

        // Checkout
        $I->click('.cart .checkout a');

        $I->seeCurrentUrlMatches('/checkout/');

        // Checkout form
        $I->seeElementInDOM('#PaymentForm_OrderForm');

        // fill all fields

        // cust details
        $I->fillField(['name' => $this->getCheckoutFormField('CustomerDetails_FirstName')], 'Acceptance');
        $I->fillField(['name' => $this->getCheckoutFormField('CustomerDetails_Surname')], 'Tester');
        $I->fillField(['name' => $this->getCheckoutFormField('CustomerDetails_Email')], 'tester@example.com');

        // shipping
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_Address')], '14 Shipping St');
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_AddressLine2')], 'Apartment 3');
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_City')], 'Sydney');
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_State')], 'NSW');
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_PostalCode')], '2000');
        $I->fillField(['name' => $this->getCheckoutFormField('ShippingAddress_Phone')], '1800 Testing');

        // billing
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_Address')], '18 Billing Road');
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_AddressLine2')], 'Unit 500');
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_City')], 'Melbourne');
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_State')], 'VIC');
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_PostalCode')], '3001');
        $I->fillField(['name' => $this->getCheckoutFormField('BillingAddress_Phone')], '0400 000 000');

        // save the note note with a timestamp for the screenshot
        $I->fillField(['name' => $this->getCheckoutFormField('Notes_Notes')], 'Automated Acceptance testing: ' . $dt_formatted);

        $I->makeScreenshot('002_checkout_form_fields_filled');

        // submit the checkout form
        $I->submitForm('#PaymentForm_OrderForm', [], 'action_checkoutSubmit');

        // resize for screenshots
        $I->resizeWindow(1920, 1600);

        $I->makeScreenshot('003_checkout_form_submitted');

        // grab payment reference from URL
        $url = $I->grabFromCurrentUrl();
        $paymentReference = $this->getPaymentReferenceFromUrl($url);
        $I->assertNotEmpty($paymentReference, "paymentReference is empty");

        // check record with reference is in the DB
        $I->seeInDatabase(
            'CppPayment',
            [
                'PaymentReference' => $paymentReference
            ]
        );

        // wait for up to 30s for payOptions to become visible
        $I->waitForElementVisible('.payOptions', 30);

        // check card option
        $I->click('.payOptions .card input[type=radio]');

        // wait for iframe to appear
        $I->wait(1);

        $I->seeElementInDOM('iframe#trustedFrame-creditCard');

        $I->makeScreenshot('004_payment_form_gateway');

        $I->switchToIFrame('trustedFrame-creditCard');

        $I->seeElementInDOM('form#creditCardForm');

        // test with an invalid card number
        $I->fillField('form#creditCardForm input[name=cardNumber]', $this->getInvalidCreditCardNumber() );
        $I->selectOption('form#creditCardForm select[name=expiryDateMonth]', $this->getMonth() );
        $I->selectOption('form#creditCardForm select[name=expiryDateYear]', (date('Y') + 1) );
        $I->fillField('form#creditCardForm input[name=cvn]',123);

        $I->makeScreenshot('005_payment_form_gateway_fields_filled');

        // back to parent frame
        $I->switchToIFrame();

        // see an click validate card button
        $I->seeElementInDOM('#validateCard');
        $I->click('#validateCard');

        $I->wait(1);

        // back into the iframe
        $I->switchToIFrame('trustedFrame-creditCard');

        // element should have an error
        $I->seeElement('#cardNumberFieldInput .fieldError');

        // screenshot this
        $I->makeScreenshot('006_payment_form_gateway_invalid');

        // assert form
        $I->seeElementInDOM('form#creditCardForm');

        // fill with valid number
        $I->fillField('form#creditCardForm input[name=cardNumber]', $this->getValidCreditCardNumber() );

        // back to parent frame
        $I->switchToIFrame();

        // see and click validate card button
        $I->seeElementInDOM('#validateCard');
        $I->click('#validateCard');

        $I->wait(1);

        // back into the iframe
        $I->switchToIFrame('trustedFrame-creditCard');

        // validation error should not be visible
        $I->dontSeeElement('#cardNumberFieldInput .fieldError');

        // back up to parent
        $I->switchToIFrame();

        // screenshot this
        $I->makeScreenshot('007_payment_form_gateway_valid');

        // check that these elements are visible
        $I->seeElementInDOM('#amount');
        $I->seeElementInDOM('#surcharge-message');
        $I->seeElementInDOM('#surcharge-amount');
        $I->seeElementInDOM('#total-amount');

        // paynow button should be visible
        $I->seeElementInDOM('#payNowBtn');

        $I->click('#payNowBtn');

        // screenshot this
        $I->makeScreenshot('008_payment_form_gateway_clickpaynow');

        $I->see('Make a payment');
        $I->see('Processing payment');

        /**
         * TODO:
         * redirect back to /success URL
         * handle cancel transaction
         */

    }

    /**
     * Return the checkout form field name
     */
    protected function getCheckoutFormField($name) : string {
        return "SilverShop-Checkout-Component-{$name}";
    }

    /**
     * Get the paymentReference from the current URI
     * @return string
     */
    protected function getPaymentReferenceFromUrl(string $url) : string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if(!$query) {
            return '';
        }
        $queryParts = [];
        parse_str($query, $queryParts);
        if(empty($queryParts['paymentReference'])) {
            return '';
        } else {
            return $queryParts['paymentReference'];
        }
    }

    /**
     * Get a random month from available selections
     */
    protected function getMonth() : string {
        $months = $this->getMonths();
        $month = array_rand($months, 1);
        return $months[ $month ];
    }

    /**
     * Get all available months
     */
    protected function  getMonths() : array {
        return [
            '01','02',
            '03','04',
            '05','06',
            '07','08',
            '09','10',
            '11','12'
        ];
    }

    /**
     * Return valid card number (VISA test number)
     */
    protected function getValidCreditCardNumber() : string {
        return "4111111111111111";
    }

    /**
     * Return invalid card number in testing
     */
    protected function getInvalidCreditCardNumber() : string {
        return "4111111111111110";
    }

}
