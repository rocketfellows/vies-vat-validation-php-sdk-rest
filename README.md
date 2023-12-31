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
- https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface.

## VIES VAT number validation REST service description.

For more information about VIES VAT number validation REST service see: https://ec.europa.eu/taxation_customs/vies/#/technical-information.

For the REST service, next urls are available:
- https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number - production;
- https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service - for test.

Swagger file: https://ec.europa.eu/assets/taxud/vow-information/swagger_publicVAT.yaml

## VIES VAT number validation PHP sdk REST component description.

`AbstractVatNumberValidationRestService` - is an abstract class that implements the interface https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface and is intended for sending a request for VAT validation using the REST web service, processing response/faults and returning an object of type validation result.

`VatNumberValidationRestService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the production api endpoint by url https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number.

`VatNumberValidationRestTestService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the test api endpoint by url https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service.

`VatNumberValidationRestExpansibleService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the service according to url, passed through the class constructor (customizable service).

For creating vat number validation result this component is using factory `rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory` from interface package https://github.com/rocketfellows/vies-vat-validation-php-sdk-interface.
According to factory, if any of required response attributes is missing, then validation process will throw special exceptions:

- `CountryCodeAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` country code attribute not found.
- `RequestDateAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` request date attribute not found.
- `ValidationFlagAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` validation flag attribute not found.
- `VatNumberAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` vat number attribute not found.
- `VatOwnerAddressAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` vat owner address attribute not found.
- `VatOwnerNameAttributeNotFoundException` - exception thrown if while creating instance of `VatNumberValidationResult` vat owner name attribute not found.

## Usage examples.

### VatNumberValidationRestService usage.

<hr>

VAT number validation result (VAT is valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

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
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

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

### VatNumberValidationRestTestService usage.

<hr>

Here is the list of VAT Number to use to receive each kind of answer:
- 100 = Valid request with Valid VAT Number
- 200 = Valid request with an Invalid VAT Number
- 201 = Error : INVALID_INPUT
- 202 = Error : INVALID_REQUESTER_INFO
- 300 = Error : SERVICE_UNAVAILABLE
- 301 = Error : MS_UNAVAILABLE
- 302 = Error : TIMEOUT
- 400 = Error : VAT_BLOCKED
- 401 = Error : IP_BLOCKED
- 500 = Error : GLOBAL_MAX_CONCURRENT_REQ
- 501 = Error : GLOBAL_MAX_CONCURRENT_REQ_TIME
- 600 = Error : MS_MAX_CONCURRENT_REQ
- 601 = Error : MS_MAX_CONCURRENT_REQ_TIME

For all the other cases, The web service will responds with a "SERVICE_UNAVAILABLE" error.

Some usage examples below.

VAT number validation result (VAT is valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestTestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestTestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

$validationResult = $service->validateVat(VatNumber::create('DE', '100'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```php
VAT country code: DE
VAT number: 100
Request date: 2023-12-29T11:37:42.466Z
Is VAT valid: true
VAT holder name: John Doe
VAT holder address: 123 Main St, Anytown, UK
```

VAT number validation result (VAT is not valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestTestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestTestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

$validationResult = $service->validateVat(VatNumber::create('DE', '200'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```php
VAT country code: DE
VAT number: 200
Request date: 2023-12-29T11:39:17.727Z
Is VAT valid: false
VAT holder name: ---
VAT holder address: ---
```

VAT number validation resulted with INVALID_INPUT fault:

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestTestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestTestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

try {
    $validationResult = $service->validateVat(VatNumber::create('DE', '201'));
} catch (Exception $exception) {
    var_dump(get_class($exception));
    var_dump($exception->getMessage());
}
```
```php
rocketfellows\ViesVatValidationInterface\exceptions\service\InvalidInputServiceException
""
```

VAT number validation resulted with IP_BLOCKED fault:

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestTestService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestTestService((new Client()), (new FaultCodeExceptionFactory()), (new VatNumberValidationResultFactory()));

try {
    $validationResult = $service->validateVat(VatNumber::create('DE', '401'));
} catch (Exception $exception) {
    var_dump(get_class($exception));
    var_dump($exception->getMessage());
}
```
```php
rocketfellows\ViesVatValidationInterface\exceptions\service\IPBlockedServiceException
""
```

### VatNumberValidationRestExpansibleService usage.

<hr>

`VatNumberValidationRestExpansibleService` - is an inheritor of the `AbstractVatNumberValidationRestService` class, configured to send a request to the service according to url, passed through the class constructor (customizable service).

For example init service with url - https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number.

VAT number validation result (VAT is valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestExpansibleService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestExpansibleService(
    'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number',
    (new Client()),
    (new FaultCodeExceptionFactory()),
    (new VatNumberValidationResultFactory())
);

$validationResult = $service->validateVat(VatNumber::create('DE', '206223519'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```php
VAT country code: DE
VAT number: 206223519
Request date: 2023-12-29T11:46:32.025Z
Is VAT valid: true
VAT holder name: ---
VAT holder address: ---
```

VAT number validation result (VAT is not valid):

```php
use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestExpansibleService;

require_once __DIR__ . '/vendor/autoload.php';

// Service initialization
$service = new VatNumberValidationRestExpansibleService(
    'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number',
    (new Client()),
    (new FaultCodeExceptionFactory()),
    (new VatNumberValidationResultFactory())
);

$validationResult = $service->validateVat(VatNumber::create('DE', '206223511'));

var_dump(sprintf('VAT country code: %s', $validationResult->getCountryCode()));
var_dump(sprintf('VAT number: %s', $validationResult->getVatNumber()));
var_dump(sprintf('Request date: %s', $validationResult->getRequestDateString()));
var_dump(sprintf('Is VAT valid: %s', $validationResult->isValid() ? 'true' : 'false'));
var_dump(sprintf('VAT holder name: %s', $validationResult->getName()));
var_dump(sprintf('VAT holder address: %s', $validationResult->getAddress()));
```
```php
VAT country code: DE
VAT number: 206223511
Request date: 2023-12-29T11:47:40.673Z
Is VAT valid: false
VAT holder name: ---
VAT holder address: ---
```

## Contributing.

Welcome to pull requests. If there is a major changes, first please open an issue for discussion.

Please make sure to update tests as appropriate.
