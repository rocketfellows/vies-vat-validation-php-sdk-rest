<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestService;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestServiceTest extends VatNumberValidationServiceTest
{
    protected const EXPECTED_URL_SOURCE = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

    protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface
    {
        return new VatNumberValidationRestService(
            $this->client,
            $this->faultCodeExceptionFactory,
            $this->vatNumberValidationResultFactory
        );
    }
}
