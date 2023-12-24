<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\exceptions\ServiceRequestException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
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

    private function getValidatingVatNumberTestValue(): VatNumber
    {
        return new VatNumber(self::COUNTRY_CODE_TEST_VALUE, self::VAT_NUMBER_TEST_VALUE);
    }
}
