<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use rocketfellows\ViesVatValidationInterface\VatNumber;

class RequestFactory
{
    private const REQUEST_PARAM_NAME_COUNTRY_CODE = 'countryCode';
    private const REQUEST_PARAM_NAME_VAT_NUMBER = 'vatNumber';

    public static function getCheckVatNumberRequestData(VatNumber $vatNumber): array
    {
        return [
            self::REQUEST_PARAM_NAME_COUNTRY_CODE => $vatNumber->getCountryCode(),
            self::REQUEST_PARAM_NAME_VAT_NUMBER => $vatNumber->getVatNumber(),
        ];
    }
}
