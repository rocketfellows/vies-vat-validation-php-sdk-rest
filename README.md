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

## VIES VAT number validation REST service description.

For more information about VIES VAT number validation REST service see: https://ec.europa.eu/taxation_customs/vies/#/technical-information.

For the REST service, next urls are available:
- https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number - production;
- https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service - for test.

## VIES VAT number validation PHP sdk SOAP component description.

`AbstractVatNumberValidationRestService` - is an abstract class that implements the interface https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface and is intended for sending a request for VAT validation using the REST web service, processing response/faults and returning an object of type validation result.

`VatNumberValidationRestService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the production api endpoint by url https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number.

`VatNumberValidationRestTestService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the test api endpoint by url https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service.

`VatNumberValidationRestExpansibleService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the service according to url, passed through the class constructor (customizable service).

## Usage examples.

### VatNumberValidationRestService usage.

<hr>

VAT number validation result (VAT is valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestService((new Client()), (new FaultCodeExceptionFactory()));

$validationResult = $service->validateVat(VatNumber::create('DE', '206223519'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```shell
VAT country code: DE
VAT number: 206223519
Request date: 2023-12-29T11:33:23.919Z
Is VAT valid: true
VAT holder name: ---
VAT holder address: ---
```

VAT number validation result (VAT is not valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestService((new Client()), (new FaultCodeExceptionFactory()));

$validationResult = $service->validateVat(VatNumber::create('DE', '206223511'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```shell
VAT country code: DE
VAT number: 206223511
Request date: 2023-12-29T11:35:01.009Z
Is VAT valid: false
VAT holder name: ---
VAT holder address: ---
```

## Contributing.

Welcome to pull requests. If there is a major changes, first please open an issue for discussion.

Please make sure to update tests as appropriate.
