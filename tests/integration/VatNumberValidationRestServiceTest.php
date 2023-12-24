<?php

namespace rocketfellows\ViesVatValidationRest\tests\integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\exceptions\service\InvalidInputServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\InvalidRequesterInfoServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\MSUnavailableServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\ServiceUnavailableException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\TimeoutServiceException;
use rocketfellows\ViesVatValidationInterface\exceptions\service\VatBlockedServiceException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;

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
     * @dataProvider getValidateVatHandlingExceptionsProvidedData
     */
    public function testValidateVatHandlingExceptions(VatNumber $vatNumber, string $expectedExceptionClass): void
    {
        $this->expectException($expectedExceptionClass);

        $this->testVatNumberValidationRestService->validateVat($vatNumber);
    }

    public function getValidateVatHandlingExceptionsProvidedData(): array
    {
        // 100 = Valid request with Valid VAT Number
		// 200 = Valid request with an Invalid VAT Number
		// 401 = Error : IP_BLOCKED
		// 500 = Error : GLOBAL_MAX_CONCURRENT_REQ
		// 501 = Error : GLOBAL_MAX_CONCURRENT_REQ_TIME
		// 600 = Error : MS_MAX_CONCURRENT_REQ
		// 601 = Error : MS_MAX_CONCURRENT_REQ_TIME

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
        ];
    }
}
