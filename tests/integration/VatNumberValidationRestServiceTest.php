<?php

namespace rocketfellows\ViesVatValidationRest\tests\integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestServiceTest extends TestCase
{
    private $vatNumberValidationRestService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vatNumberValidationRestService = new VatNumberValidationRestService(
            (new Client()),
            (new FaultCodeExceptionFactory())
        );
    }
}
