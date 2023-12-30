<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestTestService;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestTestServiceTest extends VatNumberValidationServiceTest
{
    protected const EXPECTED_URL_SOURCE = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service';

    protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface
    {
        return new VatNumberValidationRestTestService(
            $this->client,
            $this->faultCodeExceptionFactory,
            $this->vatNumberValidationResultFactory
        );
    }
}
