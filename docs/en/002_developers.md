# Develooers

## Taking payments

All payment processing is handled by the `nswdpc/omnipay-nswcpp` library acting as a configured gateway within the official `silverstripe/silverstripe-omnipay` module.

Payments are taken at the CPP Gateway, which in turn provides a controller with a payment completion request.

## Refunding payments

The module supports refunding of payments. See [the agent documentation](./003_agents.md) on how to create a refund.


## Models

The module provides 5 models:

+ Payment - representing a CPP payment
+ Disbursement - representing a disbursement when making sub agency payments within the CPP
+ PaymentMethod - one of the support CPP payment methods
+ Refund - representing a refund.

### Payment

A payment is linked to the `SilverStripe\Omnipay\Model\Payment` model. Data within the payment model is updated as a payment progresses:

When authorisation occurs:
1. onBeforeAuthorize
1. onAfterAuthorize

When authorisation completes:
1. onBeforeCompleteAuthorize
1. onAfterCompleteAuthorize

When a payment completion POST request is received
1. onBeforeCompletePurchase
1. onAfterCompletePurchase

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

https://example.com/gateway/NSWGOVCPP/complete

### Retrieve an access token

This URL is provided by the CPP

### Initiate a payment request

This URL is provided by the CPP

### Redirect the customer to the payment gateway

This URL is provided by the CPP

### Initiate a payment refund

This URL is provided by the CPP
