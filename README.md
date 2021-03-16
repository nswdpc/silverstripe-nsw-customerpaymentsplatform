# Silverstripe integration with the NSW Government Customer Payments Platform

This module provides support within a Silverstripe install to take payments for items via the [NSW Government Customer Payments Platform (CPP)](https://www.digital.nsw.gov.au/transformation/government-technology-platforms-gtp/customer-payment-platform-cpp).

The module provides an [Omnipay gateway](https://github.com/thephpleague/omnipay) within conventions defined by the official [Silverstripe Omnipay abstraction layer](https://github.com/silverstripe/silverstripe-omnipay).

You can install this module standalone or as part of a Silvershop install.

It provides:

+ A payments administration area
+ Refund options
+ Payment completion endpoint
+ A payments form

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

In your project configuration, store your `client-id` and `client-secret` values

```yml
---
# app/_config/cpp.yml
Name: 'app-cpp-payments'
After:
    - '#nswdpc-cpp-configuration'
---
NSWDPC\Payments\CPP\Configuration:
  client_id: 'my client id'
  client_secret: 'my client secret'
  jwt_secret: 'my jwt secret'
```

The payment gateway will use the `client-id` and `client-secret` values to authenticate your payment requests.

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
