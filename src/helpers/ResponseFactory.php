<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use stdClass;

class ResponseFactory
{
    public static function getVatNumberValidationResult(stdClass $responseData): VatNumberValidationResult
    {
        return VatNumberValidationResult::create(
            VatNumber::create(
                $responseData->countryCode ?? '',
                $responseData->vatNumber ?? '',
            ),
            $responseData->requestDate ?? '',
            $responseData->valid ?? false,
            $responseData->name ?? null,
            $responseData->address ?? null
        );
    }
}
