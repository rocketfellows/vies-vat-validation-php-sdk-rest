<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\VatNumber;

/**
 * @group vies-vat-validation-rest-helpers
 */
class RequestFactoryTest extends TestCase
{
    public function getCheckVatNumberRequestProvidedData(): array
    {
        return [
            'country code set, vat number set' => [
                'vatNumber' => new VatNumber('DE', '1213'),
                'expectedRequestData' => [
                    'countryCode' => 'DE',
                    'vatNumber' => '1213',
                ],
            ],
        ];
    }
}
