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
        ];
    }
}
