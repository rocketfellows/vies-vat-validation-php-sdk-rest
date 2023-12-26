<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
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
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
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

    abstract protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faultCodeExceptionFactory = new FaultCodeExceptionFactory();
        $this->client = $this->createMock(Client::class);

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
            'response country code set, vat number set, request date set, is valid, name set, address set' => [
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
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": true, "name": "foo",  "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    true,
                    'foo',
                    'bar'
                ),
            ],
            'response country code set, vat number set, request date set, not valid, name set, address set' => [
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
                'checkVatResponse' => '{"countryCode": "DE", "vatNumber": "12312312", "requestDate": "2023-11-11 23:23:23", "valid": false, "name": "foo",  "address": "bar"}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('DE', '12312312'),
                    '2023-11-11 23:23:23',
                    false,
                    'foo',
                    'bar'
                ),
            ],
            'response country code not set, vat number not set, request date not set, validation not set, name not set, address not set' => [
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
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    false,
                    null,
                    null
                ),
            ],
            'response country code empty, vat number empty, request date empty, not valid, name empty, address empty' => [
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
                'checkVatResponse' => '{"countryCode": "", "vatNumber": "", "requestDate": "", "valid": false, "name": "",  "address": ""}',
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    false,
                    '',
                    ''
                ),
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
            // TODO: more test cases needed
            // TODO
            'thrown server exception, error code set, error code unknown, error message set' => [
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
