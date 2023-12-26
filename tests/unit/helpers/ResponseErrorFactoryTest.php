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
     * @dataProvider getResponseWithErrorProvidedData
     */
    public function testIsResponseWithError(stdClass $responseData, bool $isResponseWithError): void
    {
        $this->assertEquals($isResponseWithError, ResponseErrorFactory::isResponseWithError($responseData));
    }

    public function getResponseWithErrorProvidedData(): array
    {
        return [
            'error wrappers is null' => [
                'responseData' => (object) [
                    'errorWrappers' => null,
                ],
                'isResponseWithError' => false,
            ],
            'error wrappers not found' => [
                'responseData' => (object) [],
                'isResponseWithError' => false,
            ],
            'error wrappers set and not an array' => [
                'responseData' => (object) [
                    'errorWrappers' => 1234,
                ],
                'isResponseWithError' => false,
            ],
            'error wrappers set and an empty array' => [
                'responseData' => (object) [
                    'errorWrappers' => [],
                ],
                'isResponseWithError' => true,
            ],
            'error wrappers set and an not empty array' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'error' => 'foo',
                            'message' => 'bar',
                        ]
                    ],
                ],
                'isResponseWithError' => true,
            ],
        ];
    }

    /**
     * @dataProvider getResponseErrorCodeProvidedData
     */
    public function testGetResponseErrorCode(stdClass $responseData, ?string $expectedResponseErrorCode): void
    {
        $this->assertEquals($expectedResponseErrorCode, ResponseErrorFactory::getResponseErrorCode($responseData));
    }

    public function getResponseErrorCodeProvidedData(): array
    {
        return [
            'error code set' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'error' => 'foo',
                        ]
                    ],
                ],
                'expectedResponseErrorCode' => 'foo',
            ],
            'error code empty' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'error' => '',
                        ]
                    ],
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error code is null' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'error' => null,
                        ]
                    ],
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error code not found' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) []
                    ],
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error wrappers with one empty array' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        []
                    ],
                ],
                'expectedResponseErrorMessage' => '',
            ],
            'error wrappers empty' => [
                'responseData' => (object) [
                    'errorWrappers' => [],
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error wrappers is null' => [
                'responseData' => (object) [
                    'errorWrappers' => null,
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error wrappers not an array' => [
                'responseData' => (object) [
                    'errorWrappers' => true,
                ],
                'expectedResponseErrorCode' => '',
            ],
            'error wrappers not found' => [
                'responseData' => (object) [],
                'expectedResponseErrorCode' => '',
            ],
        ];
    }

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
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'message' => 'foo',
                        ]
                    ],
                ],
                'expectedResponseErrorMessage' => 'foo',
            ],
            'error message empty' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'message' => '',
                        ]
                    ],
                ],
                'expectedResponseErrorMessage' => '',
            ],
            'error message is null' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) [
                            'message' => null,
                        ]
                    ],
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers with one empty object' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        (object) []
                    ],
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers with one empty array' => [
                'responseData' => (object) [
                    'errorWrappers' => [
                        []
                    ],
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers empty' => [
                'responseData' => (object) [
                    'errorWrappers' => [],
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers is null' => [
                'responseData' => (object) [
                    'errorWrappers' => null,
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers not an array' => [
                'responseData' => (object) [
                    'errorWrappers' => true,
                ],
                'expectedResponseErrorMessage' => null,
            ],
            'error wrappers not found' => [
                'responseData' => (object) [],
                'expectedResponseErrorMessage' => null,
            ],
        ];
    }
}
