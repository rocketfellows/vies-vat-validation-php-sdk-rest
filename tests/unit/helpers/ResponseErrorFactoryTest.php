<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationRest\helpers\ResponseErrorFactory;
use stdClass;

/**
 * @group vies-vat-validation-rest-helpers
 */
class ResponseErrorFactoryTest extends TestCase
{
    /**
     * @dataProvider getResponseErrorMessageProvidedData
     */
    public function testGetResponseErrorMessage(stdClass $responseData, ?string $expectedResponseErrorMessage): void
    {
        $this->assertEquals(
            $expectedResponseErrorMessage,
            ResponseErrorFactory::getResponseErrorMessage($responseData)
        );
    }

    public function getResponseErrorMessageProvidedData(): array
    {
        return [
            'error message set' => [
                'responseData' => (object)[
                    'errorWrappers' => [
                        (object)[
                            'message' => 'foo',
                        ]
                    ],
                ],
                'expectedResponseErrorMessage' => 'foo',
            ],
            'error message empty' => [
                'responseData' => (object)[
                    'errorWrappers' => [
                        (object)[
                            'message' => '',
                        ]
                    ],
                ],
                'expectedResponseErrorMessage' => '',
            ],
        ];
    }
}
