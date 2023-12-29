<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationRest\helpers\RequestFactory;

/**
 * @group vies-vat-validation-rest-helpers
 */
class RequestFactoryTest extends TestCase
{
    /**
     * @dataProvider getCheckVatNumberRequestProvidedData
     */
    public function testGetCheckVatNumberRequestData(VatNumber $vatNumber, array $expectedRequestData): void
    {
        $this->assertEquals($expectedRequestData, RequestFactory::getCheckVatNumberRequestData($vatNumber));
    }

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
            'country code not set, vat number not set' => [
                'vatNumber' => new VatNumber('', ''),
                'expectedRequestData' => [
                    'countryCode' => '',
                    'vatNumber' => '',
                ],
            ],
        ];
    }
}
