<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;

/**
 * @group vies-vat-validation-rest-helpers
 */
class ResponseFactoryTest extends TestCase
{
    public function getVatNumberValidationResultProvidedData(): array
    {
        return [
            'country code set, vat number set, request date set, validation flag set true, name set, address set' => [
                'responseData' => (object)[
                    'countryCode' => 'DE',
                    'vatNumber' => '1234',
                    'requestDate' => '2023-12-12 10:10:10',
                    'valid' => true,
                    'name' => 'foo',
                    'address' => 'bar',
                ],
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    (new VatNumber('DE', '1234')),
                    '2023-12-12 10:10:10',
                    true,
                    'foo',
                    'bar'
                ),
            ],
            'country code set, vat number set, request date set, validation flag set false, name set, address set' => [
                'responseData' => (object)[
                    'countryCode' => 'DE',
                    'vatNumber' => '1234',
                    'requestDate' => '2023-12-12 10:10:10',
                    'valid' => false,
                    'name' => 'foo',
                    'address' => 'bar',
                ],
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    (new VatNumber('DE', '1234')),
                    '2023-12-12 10:10:10',
                    false,
                    'foo',
                    'bar'
                ),
            ],
            'country code empty, vat number empty, request date empty, validation flag empty, name empty, address empty' => [
                'responseData' => (object)[
                    'countryCode' => '',
                    'vatNumber' => '',
                    'requestDate' => '',
                    'valid' => false,
                    'name' => '',
                    'address' => '',
                ],
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    (new VatNumber('', '')),
                    '',
                    false,
                    '',
                    ''
                ),
            ],
            'country code not set, vat number not set, request date not set, validation flag not set, name not set, address not set' => [
                'responseData' => (object)[],
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    (new VatNumber('', '')),
                    '',
                    false,
                    '',
                    ''
                ),
            ],
        ];
    }
}
