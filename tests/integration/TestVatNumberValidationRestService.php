<?php

namespace rocketfellows\ViesVatValidationRest\tests\integration;

use rocketfellows\ViesVatValidationRest\AbstractVatNumberValidationRestService;

class TestVatNumberValidationRestService extends AbstractVatNumberValidationRestService
{
    protected function getUrl(): string
    {
        return 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-test-service';
    }
}
