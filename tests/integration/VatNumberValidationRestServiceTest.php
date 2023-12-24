<?php

namespace rocketfellows\ViesVatValidationRest\tests\integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
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
use rocketfellows\ViesVatValidationInterface\exceptions\service\VatBlockedServiceException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestServiceTest extends TestCase
{
    private $testVatNumberValidationRestService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testVatNumberValidationRestService = new TestVatNumberValidationRestService(
            (new Client()),
            (new FaultCodeExceptionFactory())
        );
    }

    /**
     * @dataProvider getValidateVatProvidedData
     */
    public function testValidateVat(VatNumber $vatNumber, array $expectedValidationResultData): void
    {
        $actualValidationResult = $this->testVatNumberValidationRestService->validateVat($vatNumber);

        $this->assertEquals($expectedValidationResultData['countryCode'], $actualValidationResult->getCountryCode());
        $this->assertEquals($expectedValidationResultData['vatNumber'], $actualValidationResult->getVatNumber());
        $this->assertEquals($expectedValidationResultData['isValid'], $actualValidationResult->isValid());
        $this->assertEquals($expectedValidationResultData['name'], $actualValidationResult->getName());
        $this->assertEquals($expectedValidationResultData['address'], $actualValidationResult->getAddress());
    }

    public function getValidateVatProvidedData(): array
    {
        return [
            'valid vat' => [
                'vatNumber' => new VatNumber('DE', '100'),
                'expectedValidationResultData' => [
                    'countryCode' => 'DE',
                    'vatNumber' => '100',
                    'isValid' => true,
                    'name' => 'John Doe',
                    'address' => '123 Main St, Anytown, UK',
                ],
            ],
            'invalid vat' => [
                'vatNumber' => new VatNumber('DE', '200'),
                'expectedValidationResultData' => [
                    'countryCode' => 'DE',
                    'vatNumber' => '200',
                    'isValid' => false,
                    'name' => '---',
                    'address' => '---',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValidateVatHandlingExceptionsProvidedData
     */
    public function testValidateVatHandlingExceptions(VatNumber $vatNumber, string $expectedExceptionClass): void
    {
        $this->expectException($expectedExceptionClass);

        $this->testVatNumberValidationRestService->validateVat($vatNumber);
    }

    public function getValidateVatHandlingExceptionsProvidedData(): array
    {
        return [
            'INVALID_INPUT error' => [
                'vatNumber' => new VatNumber('DE', '201'),
                'expectedExceptionClass' => InvalidInputServiceException::class,
            ],
            'INVALID_REQUESTER_INFO error' => [
                'vatNumber' => new VatNumber('DE', '202'),
                'expectedExceptionClass' => InvalidRequesterInfoServiceException::class,
            ],
            'SERVICE_UNAVAILABLE error' => [
                'vatNumber' => new VatNumber('DE', '300'),
                'expectedExceptionClass' => ServiceUnavailableException::class,
            ],
            'MS_UNAVAILABLE error' => [
                'vatNumber' => new VatNumber('DE', '301'),
                'expectedExceptionClass' => MSUnavailableServiceException::class,
            ],
            'TIMEOUT error' => [
                'vatNumber' => new VatNumber('DE', '302'),
                'expectedExceptionClass' => TimeoutServiceException::class,
            ],
            'VAT_BLOCKED error' => [
                'vatNumber' => new VatNumber('DE', '400'),
                'expectedExceptionClass' => VatBlockedServiceException::class,
            ],
            'IP_BLOCKED error' => [
                'vatNumber' => new VatNumber('DE', '401'),
                'expectedExceptionClass' => IPBlockedServiceException::class,
            ],
            'GLOBAL_MAX_CONCURRENT_REQ error' => [
                'vatNumber' => new VatNumber('DE', '500'),
                'expectedExceptionClass' => GlobalMaxConcurrentReqServiceException::class,
            ],
            'GLOBAL_MAX_CONCURRENT_REQ_TIME error' => [
                'vatNumber' => new VatNumber('DE', '501'),
                'expectedExceptionClass' => GlobalMaxConcurrentReqTimeServiceException::class,
            ],
            'MS_MAX_CONCURRENT_REQ error' => [
                'vatNumber' => new VatNumber('DE', '600'),
                'expectedExceptionClass' => MSMaxConcurrentReqServiceException::class,
            ],
            'MS_MAX_CONCURRENT_REQ_TIME error' => [
                'vatNumber' => new VatNumber('DE', '601'),
                'expectedExceptionClass' => MSMaxConcurrentReqTimeServiceException::class,
            ],
        ];
    }
}
