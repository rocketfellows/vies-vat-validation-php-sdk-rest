<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\services\VatNumberValidationRestExpansibleService;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestExpansibleServiceTest extends VatNumberValidationServiceTest
{
    protected const EXPECTED_URL_SOURCE = 'foo';

    protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface
    {
        return new VatNumberValidationRestExpansibleService(
            self::EXPECTED_URL_SOURCE,
            $this->client,
            $this->faultCodeExceptionFactory,
            $this->vatNumberValidationResultFactory
        );
    }
}
