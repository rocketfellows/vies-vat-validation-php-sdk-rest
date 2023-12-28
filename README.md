# VIES VAT number validation PHP sdk REST.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat)
![Code Coverage Badge](./badge.svg)

An implementation of interface https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface for the VIES service for checking the validity of the VAT number via the REST web services.
The implementation is designed to send a request and process a response from the VAT validation service via the REST web services.

For more information about VIES VAT number validation services via the REST web services see https://ec.europa.eu/taxation_customs/vies/#/technical-information.

## Installation.

```shell
composer require rocketfellows/vies-vat-validation-php-sdk-rest
```

## Dependencies.

Current implementation dependencies:
- guzzle client - https://github.com/guzzle/guzzle;
- https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface v1.1.0.

## Contributing.

Welcome to pull requests. If there is a major changes, first please open an issue for discussion.

Please make sure to update tests as appropriate.
