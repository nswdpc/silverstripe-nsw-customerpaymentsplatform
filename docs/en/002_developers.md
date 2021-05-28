# Developers

## Taking payments

All payment processing is handled by the `nswdpc/omnipay-nswcpp` library acting as a configured gateway within the official `silverstripe/silverstripe-omnipay` module.

Payments are taken at the CPP Gateway, which in turn POSTs a payment completion request to the agency website with payment data using a JWT.

## Refunding payments

The module supports refunding of payments. See [the agent documentation](./003_agents.md) on how to create a refund.


## Models

The module provides 2 models:

+ Payment - representing a CPP payment, linked to a `\SilverStripe\Omnipay\Model\Payment`
+ :warning: Disbursement - representing a disbursement when making sub agency payments within the CPP (todo)

### Payment

A CPP payment is linked to the `SilverStripe\Omnipay\Model\Payment` model. Data within the payment model is updated as a payment progresses.

1. A payment request is made to the CPP, returning a `paymentReference`
1. The payment stores the `paymentReference` value
1. The payer is redirected, with that paymentReference value, to the CPP gateway 
    1. The payer can cancel at this point
1. The payer processes their payment using the relevant payment method
    1. Upon successful payment, the CPP sends a JWT to the agency website, which is decoded,validated and responded to.
    1. Successful payments are then automatically redirected to the configured payment success URL on the agency website
1. Upon unsuccessful payment or cancellation, the payer can retry or cancel and return to the agency website, possibly to try again.

### Disbursement

A payment can have multiple disbursements.

### Payment method

The payment method used by the customer is updated when the payment completion POST request is received

### Refund

A refund record can be created in the administration area and then processed.

Once the refund is successfully processed, a refund reference will be saved to the Refund record.

## Controllers

The `SilverStripe\Omnipay\PaymentGatewayController` controller provides handling for all controller requests.

## URLs

### Handle payment completion request

This URL is provided to the CPP which will send a POST request upon successful payment by the customer:

https://agency.example.com/gateway/NSWGOVCPP/complete/

### Payment success request

This URL is provided to the CPP. After successful payment, the payer's browser will be directed to this URL

https://agency.example.com/gateway/NSWGOVCPP/success/

### Payment cancel request

This URL is provided to the CPP. if the payer cancels the payment request, the payer's browser will be directed to this URL

https://agency.example.com/gateway/NSWGOVCPP/success/

### Retrieve an access token

This URL is provided by the CPP

### Initiate a payment request

This URL is provided by the CPP

### Redirect the customer to the payment gateway

This URL is provided by the CPP

### Initiate a payment refund

This URL is provided by the CPP
