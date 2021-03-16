# Documentation

## For developers

### Taking payments

All payment processing is handled by the `nswdpc/omnipay-nswcpp` module.

Payments are taken at the CPP Gateway, which in turn provides a controller with a payment completion request.

### Refunding payments

The module supports voiding of payments. See user documentation, below.

## For website owners

### Viewing payments

You may view payment attempts in the CPP administration area. Log in to view these.

### Refunding payments

Navigate to the CPP administration area to find a successul payment. Click the `Refund` button to void this payment.
