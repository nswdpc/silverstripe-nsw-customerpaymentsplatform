# Silverstripe integration with the NSW Government Customer Payments Platform

> ⚠️ This module is a WIP. Do not use it in production as the API will change.

This module provides support within a Silverstripe install to take payments from citizens for items via the [NSW Government Customer Payments Platform (CPP)](https://cpp-info-hub.service.nsw.gov.au/).

## Features

+ A payments administration area
+ Refund options
+ Disbursements (sub agencies)
+ Payment completion endpoint
+ A payments form

You can install this module standalone and integrate it yourself or as part of a [Silvershop](https://github.com/silvershop/silvershop-core) install.

## Omnipay resources

+ The [Omnipay](https://github.com/thephpleague/omnipay) project
+ [Silverstripe Omnipay](https://github.com/silverstripe/silverstripe-omnipay) module
+ [NSW DPC Omnipay gateway for the Customer Payments Platform](https://github.com/nswdpc/omnipay-nswcpp)

## Requirements

+ nswdpc/omnipay-nswcpp - provides a gateway to the Customer Payments platform, including payment completion and refund handling
+ omnipay/common - Core components for the Omnipay PHP payment processing library
+ silverstripe/silverstripe-omnipay - the official Omnipay integration with Silverstripe


## Roadmap

+ User defined form integration
+ End of day reconciliation job

### Installation

The only supported method of installing this module and its requirements is via `composer` as part of a Silverstripe install.

```shell
composer require nswdpc/silverstripe-nsw-customerpaymentsplatform
```

## Requisites

To take payments with this module, you must

- have an approved Agency account within the CPP
- be able to configure the provided OAuth2 client-id and client-secret + your JWT secret key within a Silverstripe install
- have an appropriate post-payment product fulfilment process

## License

This module is made available as an Open Source project under the [BSD-3-Clause](./LICENSE.md) license.

## Documentation

[Further documentation is available](./docs/en/001_index.md)

## Configuration

Configuration is completed per-environment using the `SilverStripe\Omnipay\GatewayInfo` convention:

```yml
---
# app/_config/cpp.yml
Name: 'app-cpp-payments'
After:
    - '#nswdpc-cpp-configuration'
---
SilverStripe\Omnipay\GatewayInfo:
  NSWGOVCPP:
    parameters:
      clientId: 'a client id'
      clientSecret: 'a client secret'
      jwtPublicKey: 'a JWT public key'
      accessTokenUrl: 'https://access.example.com/token'
      requestPaymentUrl: 'https://payment.example.com/request'
      gatewayUrl: 'https://payment.example.com/pay'
      refundUrl: 'https://payment.example.com/refund'
      testMode: false
```

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)


## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Security

If you have found a security issue with this module, please email digital[@]dpc.nsw.gov.au in the first instance, detailing your findings.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
