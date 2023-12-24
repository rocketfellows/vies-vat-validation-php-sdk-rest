<?php

namespace rocketfellows\ViesVatValidationRest\services;

use rocketfellows\ViesVatValidationRest\AbstractVatNumberValidationRestService;

class VatNumberValidationRestService extends AbstractVatNumberValidationRestService
{
    protected function getUrl(): string
    {
        return 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';
    }
}
