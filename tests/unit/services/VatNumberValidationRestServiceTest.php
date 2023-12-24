<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\services;

use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;

/**
 * @group vies-vat-validation-rest
 */
class VatNumberValidationRestServiceTest extends VatNumberValidationServiceTest
{
    protected function getVatNumberValidationRestService(): VatNumberValidationServiceInterface
    {
        return new VatNumberValidationRestService($this->client, $this->faultCodeExceptionFactory);
    }
}
