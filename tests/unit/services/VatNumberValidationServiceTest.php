<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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
            /*'response country code empty, vat number empty, request date empty, not valid, name empty, address empty' => [
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
                'checkVatResponse' => (object) [
                    'countryCode' => '',
                    'vatNumber' => '',
                    'requestDate' => '',
                    'valid' => false,
                    'name' => '',
                    'address' => '',
                ],
                'expectedVatNumberValidationResult' => new VatNumberValidationResult(
                    new VatNumber('', ''),
                    '',
                    false,
                    '',
                    ''
                ),
            ],*/
        ];
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
}
