<?php

namespace rocketfellows\ViesVatValidationRest\tests\integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;

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

    public function testValidateVatHandlingExceptions(): void
    {
        // TODO: implement
    }
}
