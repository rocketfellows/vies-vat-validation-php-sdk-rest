<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use rocketfellows\ViesVatValidationInterface\exceptions\service\GlobalMaxConcurrentReqServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\GlobalMaxConcurrentReqTimeServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\InvalidInputServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\InvalidRequesterInfoServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\IPBlockedServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\MSMaxConcurrentReqServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\MSMaxConcurrentReqTimeServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\MSUnavailableServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\ServiceUnavailableException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\TimeoutServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\UnknownServiceErrorException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\VatBlockedServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\ServiceRequestException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\CountryCodeAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\RequestDateAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\ValidationFlagAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\VatNumberAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\VatOwnerAddressAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\exceptions\validationResult\VatOwnerNameAttributeNotFoundException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;

/**
 * @group vies-vat-validation-rest
 */
abstract class VatNumberValidationServiceTest extends TestCase
{
    private const EXPECTED_INTERFACE_IMPLEMENTATIONS = [
        VatNumberValidationServiceInterface::class,
    ];

    protected const EXPECTED_URL_SOURCE = '';

    private const COUNTRY_CODE_TEST_VALUE = 'DE';
    private const VAT_NUMBER_TEST_VALUE = '123123';

    protected $vatNumberValidationRestService;
    protected $faultCodeExceptionFactory;
    protected $client;
    protected $vatNumberValidationResultFactory;

    abstract protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faultCodeExceptionFactory = new FaultCodeExceptionFactory();
        $this->client = $this->createMock(Client::class);
        $this->vatNumberValidationResultFactory = new VatNumberValidationResultFactory();

        $this->vatNumberValidationRestService = $this->getVatNumberValidationRestService();
    }

    public function testVatNumberValidationSoapServiceImplementedInterfaces(): void
    {
        foreach (self::EXPECTED_INTERFACE_IMPLEMENTATIONS as $expectedInterfaceImplementation) {
            $this->assertInstanceOf($expectedInterfaceImplementation, $this->vatNumberValidationRestService);
        }
    }

    public function testHandleCheckVatException(): void
    {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(
                $this::EXPECTED_URL_SOURCE,
                ['json' => ['countryCode' => self::COUNTRY_CODE_TEST_VALUE, 'vatNumber' => self::VAT_NUMBER_TEST_VALUE],]
            )
            ->willThrowException($this->createMock(GuzzleException::class));

        $this->expectException(ServiceRequestException::class);

        $this->vatNumberValidationRestService->validateVat(
            $this->getValidatingVatNumberTestValue()
        );
    }

    /**
     * @dataProvider getCheckVatProvidedData
     */
    public function testSuccessCheckVat(
        VatNumber $vatNumber,
        array $checkVatCallArgs,
        string $checkVatResponse,
        VatNumberValidationResult $expectedVatNumberValidationResult
    ): void {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(...$checkVatCallArgs)
            ->willReturn($this->getResponseMock(['body' => $checkVatResponse,]));

        $this->assertEquals(
            $expectedVatNumberValidationResult,
            $this->vatNumberValidationRestService->validateVat($vatNumber)
        );
    }

    public function getCheckVatProvidedData(): array
    {
        return [
            'response attributes in camel case, response country code set, vat number set, request date set, is valid, name set, address set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    true,
                    'foo',
                    'bar'
                ),
            ],
            'response attributes in snake case, response country code set, vat number set, request date set, is valid, name set, address set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    true,
                    'foo',
                    'bar'
                ),
            ],
            'response attributes in camel case, response country code set, vat number set, request date set, not valid, name set, address set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": false, "name": "foo", "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    false,
                    'foo',
                    'bar'
                ),
            ],
            'response attributes in snake case, response country code set, vat number set, request date set, not valid, name set, address set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "valid": false, "name": "foo", "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    false,
                    'foo',
                    'bar'
                ),
            ],
            'response attributes in camel case, response country code empty, vat number empty, request date empty, not valid, name empty, address empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "", "vatNumber": "", "requestDate": "", "valid": false, "name": "", "address": ""}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    false,
                    '',
                    ''
                ),
            ],
            'response attributes in snake case, response country code empty, vat number empty, request date empty, not valid, name empty, address empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "", "vat_number": "", "request_date": "", "valid": false, "name": "", "address": ""}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    false,
                    '',
                    ''
                ),
            ],
            'response attributes in camel case, response country code empty, vat number empty, request date empty, is valid, name empty, address empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "", "vatNumber": "", "requestDate": "", "valid": true, "name": "", "address": ""}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    true,
                    '',
                    ''
                ),
            ],
            'response attributes in snake case, response country code empty, vat number empty, request date empty, is valid, name empty, address empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "", "vat_number": "", "request_date": "", "valid": true, "name": "", "address": ""}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    true,
                    '',
                    ''
                ),
            ],
        ];
    }

    /**
     * @dataProvider getCheckVatWithDifferentSetOfAttributesInResponseProvidedData
     */
    public function testHandlingCheckVatWithDifferentSetOfAttributesInResponse(
        VatNumber $vatNumber,
        array $checkVatCallArgs,
        string $checkVatResponse,
        string $expectedExceptionClass
    ): void {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(...$checkVatCallArgs)
            ->willReturn($this->getResponseMock(['body' => $checkVatResponse,]));

        $this->expectException($expectedExceptionClass);

        $this->vatNumberValidationRestService->validateVat($vatNumber);
    }

    public function getCheckVatWithDifferentSetOfAttributesInResponseProvidedData(): array
    {
        return [
            'response attributes in camel case, country code response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => CountryCodeAttributeNotFoundException::class,
            ],
            'response attributes in snake case, country code response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => CountryCodeAttributeNotFoundException::class,
            ],
            'response attributes in camel case, vat number response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "requestDate": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => VatNumberAttributeNotFoundException::class,
            ],
            'response attributes in snake case, vat number response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "request_date": "2023-11-11 23:23:23", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => VatNumberAttributeNotFoundException::class,
            ],
            'response attributes in camel case, request date response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => RequestDateAttributeNotFoundException::class,
            ],
            'response attributes in snake case, request date response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "valid": true, "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => RequestDateAttributeNotFoundException::class,
            ],
            'response attributes in camel case, validation flag response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => ValidationFlagAttributeNotFoundException::class,
            ],
            'response attributes in snake case, validation flag response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "name": "foo", "address": "bar"}',
                'expectedExceptionClass' => ValidationFlagAttributeNotFoundException::class,
            ],
            'response attributes in camel case, name response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": true, "address": "bar"}',
                'expectedExceptionClass' => VatOwnerNameAttributeNotFoundException::class,
            ],
            'response attributes in snake case, name response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "valid": true, "address": "bar"}',
                'expectedExceptionClass' => VatOwnerNameAttributeNotFoundException::class,
            ],
            'response attributes in camel case, address response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": true, "name": "foo"}',
                'expectedExceptionClass' => VatOwnerAddressAttributeNotFoundException::class,
            ],
            'response attributes in snake case, address response attribute not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{"country_code": "DE", "vat_number": "12312312", "request_date": "2023-11-11 23:23:23", "valid": true, "name": "foo"}',
                'expectedExceptionClass' => VatOwnerAddressAttributeNotFoundException::class,
            ],
            'response attributes not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponse' => '{}',
                'expectedExceptionClass' => CountryCodeAttributeNotFoundException::class,
            ],
        ];
    }

    /**
     * @dataProvider getHandlingCheckVatFaultProvidedData
     */
    public function testHandleCheckVatFault(
        VatNumber $vatNumber,
        array $checkVatCallArgs,
        string $checkVatResponseFault,
        string $expectedExceptionClass
    ): void {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(...$checkVatCallArgs)
            ->willReturn($this->getResponseMock(['body' => $checkVatResponseFault,]));

        $this->expectException($expectedExceptionClass);

        $this->vatNumberValidationRestService->validateVat($vatNumber);
    }

    public function getHandlingCheckVatFaultProvidedData(): array
    {
        return [
            'INVALID_INPUT fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "INVALID_INPUT"}]}',
                'expectedExceptionClass' => InvalidInputServiceException::class,
            ],
            'invalid_input fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "invalid_input"}]}',
                'expectedExceptionClass' => InvalidInputServiceException::class,
            ],
            'SERVICE_UNAVAILABLE fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE"}]}',
                'expectedExceptionClass' => ServiceUnavailableException::class,
            ],
            'service_unavailable fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "service_unavailable"}]}',
                'expectedExceptionClass' => ServiceUnavailableException::class,
            ],
            'MS_UNAVAILABLE fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE"}]}',
                'expectedExceptionClass' => MSUnavailableServiceException::class,
            ],
            'ms_unavailable fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "ms_unavailable"}]}',
                'expectedExceptionClass' => MSUnavailableServiceException::class,
            ],
            'TIMEOUT fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "TIMEOUT"}]}',
                'expectedExceptionClass' => TimeoutServiceException::class,
            ],
            'timeout fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "timeout"}]}',
                'expectedExceptionClass' => TimeoutServiceException::class,
            ],
            'INVALID_REQUESTER_INFO fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO"}]}',
                'expectedExceptionClass' => InvalidRequesterInfoServiceException::class,
            ],
            'invalid_requester_info fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "invalid_requester_info"}]}',
                'expectedExceptionClass' => InvalidRequesterInfoServiceException::class,
            ],
            'VAT_BLOCKED fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "VAT_BLOCKED"}]}',
                'expectedExceptionClass' => VatBlockedServiceException::class,
            ],
            'vat_blocked fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "vat_blocked"}]}',
                'expectedExceptionClass' => VatBlockedServiceException::class,
            ],
            'IP_BLOCKED fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "IP_BLOCKED"}]}',
                'expectedExceptionClass' => IPBlockedServiceException::class,
            ],
            'ip_blocked fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "ip_blocked"}]}',
                'expectedExceptionClass' => IPBlockedServiceException::class,
            ],
            'GLOBAL_MAX_CONCURRENT_REQ fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ"}]}',
                'expectedExceptionClass' => GlobalMaxConcurrentReqServiceException::class,
            ],
            'global_max_concurrent_req fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "global_max_concurrent_req"}]}',
                'expectedExceptionClass' => GlobalMaxConcurrentReqServiceException::class,
            ],
            'GLOBAL_MAX_CONCURRENT_REQ_TIME fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME"}]}',
                'expectedExceptionClass' => GlobalMaxConcurrentReqTimeServiceException::class,
            ],
            'global_max_concurrent_req_time fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "global_max_concurrent_req_time"}]}',
                'expectedExceptionClass' => GlobalMaxConcurrentReqTimeServiceException::class,
            ],
            'MS_MAX_CONCURRENT_REQ fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ"}]}',
                'expectedExceptionClass' => MSMaxConcurrentReqServiceException::class,
            ],
            'ms_max_concurrent_req fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "ms_max_concurrent_req"}]}',
                'expectedExceptionClass' => MSMaxConcurrentReqServiceException::class,
            ],
            'MS_MAX_CONCURRENT_REQ_TIME fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME"}]}',
                'expectedExceptionClass' => MSMaxConcurrentReqTimeServiceException::class,
            ],
            'ms_max_concurrent_req_time fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "ms_max_concurrent_req_time"}]}',
                'expectedExceptionClass' => MSMaxConcurrentReqTimeServiceException::class,
            ],
            'unknown fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": "foo"}]}',
                'expectedExceptionClass' => UnknownServiceErrorException::class,
            ],
            'empty fault' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": ""}]}',
                'expectedExceptionClass' => UnknownServiceErrorException::class,
            ],
            'fault null' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": [{"error": null}]}',
                'expectedExceptionClass' => UnknownServiceErrorException::class,
            ],
            'error wrappers empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'checkVatResponseFault' => '{"errorWrappers": []}',
                'expectedExceptionClass' => UnknownServiceErrorException::class,
            ],
        ];
    }

    /**
     * @dataProvider getValidateVatHandlingRequestExceptionsProvidedData
     */
    public function testValidateVatHandlingRequestExceptions(
        VatNumber $vatNumber,
        array $checkVatCallArgs,
        Exception $thrownRequestException,
        Exception $expectedException
    ): void {
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with(...$checkVatCallArgs)
            ->willThrowException($thrownRequestException);

        $this->expectExceptionObject($expectedException);

        $this->vatNumberValidationRestService->validateVat($vatNumber);
    }

    public function getValidateVatHandlingRequestExceptionsProvidedData(): array
    {
        return [
            'thrown client exception, error code unknown, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo", "message": "bar"}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code unknown, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo", "message": ""}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code unknown, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo"}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code empty, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": ""}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code not set, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, errors block empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": []}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, errors block not an array' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": null}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, errors block not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, response empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_INPUT, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT", "message": "bar"}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_INPUT, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT", "message": ""}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_INPUT, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT"}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code SERVICE_UNAVAILABLE, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE", "message": "bar"}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code SERVICE_UNAVAILABLE, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE", "message": ""}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code SERVICE_UNAVAILABLE, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE"}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_UNAVAILABLE, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_UNAVAILABLE, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE", "message": ""}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_UNAVAILABLE, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE"}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code TIMEOUT, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT", "message": "bar"}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code TIMEOUT, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT", "message": ""}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code TIMEOUT, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT"}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_REQUESTER_INFO, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO", "message": "bar"}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_REQUESTER_INFO, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO", "message": ""}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code INVALID_REQUESTER_INFO, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO"}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code VAT_BLOCKED, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED", "message": "bar"}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code VAT_BLOCKED, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED", "message": ""}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code VAT_BLOCKED, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED"}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code IP_BLOCKED, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED", "message": "bar"}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code IP_BLOCKED, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED", "message": ""}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code IP_BLOCKED, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED"}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ", "message": "bar"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ", "message": ""}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME", "message": "bar"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME", "message": ""}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ", "message": ""}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME", "message": ""}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown client exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getClientExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            // TODO: more test cases needed
            // TODO
            'thrown server exception, error code unknown, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo", "message": "bar"}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code unknown, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo", "message": ""}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code unknown, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "foo"}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    'foo',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code empty, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": ""}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code not set, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{}]}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, errors block empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": []}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, errors block not an array' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": null}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, errors block not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{}']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, response empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '']),
                ]),
                'expectedException' => new UnknownServiceErrorException(
                    '',
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_INPUT, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT", "message": "bar"}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_INPUT, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT", "message": ""}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_INPUT, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_INPUT"}]}']),
                ]),
                'expectedException' => new InvalidInputServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code SERVICE_UNAVAILABLE, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE", "message": "bar"}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code SERVICE_UNAVAILABLE, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE", "message": ""}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code SERVICE_UNAVAILABLE, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "SERVICE_UNAVAILABLE"}]}']),
                ]),
                'expectedException' => new ServiceUnavailableException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_UNAVAILABLE, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_UNAVAILABLE, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE", "message": ""}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_UNAVAILABLE, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_UNAVAILABLE"}]}']),
                ]),
                'expectedException' => new MSUnavailableServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code TIMEOUT, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT", "message": "bar"}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code TIMEOUT, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT", "message": ""}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code TIMEOUT, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "TIMEOUT"}]}']),
                ]),
                'expectedException' => new TimeoutServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_REQUESTER_INFO, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO", "message": "bar"}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_REQUESTER_INFO, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO", "message": ""}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code INVALID_REQUESTER_INFO, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "INVALID_REQUESTER_INFO"}]}']),
                ]),
                'expectedException' => new InvalidRequesterInfoServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code VAT_BLOCKED, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED", "message": "bar"}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code VAT_BLOCKED, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED", "message": ""}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code VAT_BLOCKED, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "VAT_BLOCKED"}]}']),
                ]),
                'expectedException' => new VatBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code IP_BLOCKED, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED", "message": "bar"}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code IP_BLOCKED, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED", "message": ""}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code IP_BLOCKED, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "IP_BLOCKED"}]}']),
                ]),
                'expectedException' => new IPBlockedServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ", "message": "bar"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ", "message": ""}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME", "message": "bar"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME", "message": ""}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code GLOBAL_MAX_CONCURRENT_REQ_TIME, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "GLOBAL_MAX_CONCURRENT_REQ_TIME"}]}']),
                ]),
                'expectedException' => new GlobalMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ", "message": ""}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME", "message": "bar"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    'bar',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message empty' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME", "message": ""}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
            'thrown server exception, error code set, error code MS_MAX_CONCURRENT_REQ_TIME, error message not set' => [
                'vatNumber' => new VatNumber(
                    'DE',
                    '12312312'
                ),
                'checkVatCallArgs' => [
                    $this::EXPECTED_URL_SOURCE,
                    [
                        'json' => [
                            'countryCode' => 'DE',
                            'vatNumber' => '12312312',
                        ],
                    ]
                ],
                'thrownRequestException' => $this->getServerExceptionMock([
                    'response' => $this->getResponseMock(['body' => '{"errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ_TIME"}]}']),
                ]),
                'expectedException' => new MSMaxConcurrentReqTimeServiceException(
                    '',
                    0,
                    null
                ),
            ],
        ];
    }

    public function testValidateVatHandlingCommonRequestException(): void
    {
        $vatNumber = $this->getValidatingVatNumberTestValue();

        $this->client
            ->expects($this->once())
            ->method('post')
            ->willThrowException($this->getCommonRequestExceptionMock());

        $this->expectException(ServiceRequestException::class);

        $this->vatNumberValidationRestService->validateVat($vatNumber);
    }

    private function getResponseMock(array $params = []): MockObject
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($params['body'] ?? '');

        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('getBody')->willReturn($stream);

        return $mock;
    }

    private function getValidatingVatNumberTestValue(): VatNumber
    {
        return new VatNumber(self::COUNTRY_CODE_TEST_VALUE, self::VAT_NUMBER_TEST_VALUE);
    }

    private function getClientExceptionMock(array $params = []): MockObject
    {
        $mock = $this->createMock(ClientException::class);
        $mock->method('getResponse')->willReturn($params['response'] ?? $this->getResponseMock());

        return $mock;
    }

    private function getServerExceptionMock(array $params = []): MockObject
    {
        $mock = $this->createMock(ServerException::class);
        $mock->method('getResponse')->willReturn($params['response'] ?? $this->getResponseMock());

        return $mock;
    }

    private function getCommonRequestExceptionMock(): MockObject
    {
        $mock = $this->createMock(GuzzleException::class);

        return $mock;
    }
}
