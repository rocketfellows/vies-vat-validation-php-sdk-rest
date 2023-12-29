<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use Psr\Http\Message\ResponseInterface;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use stdClass;

class ResponseFactory
{
    public static function isResponseDataEmpty(stdClass $responseData): bool
    {
        return empty((array) $responseData);
    }

    public static function getResponseData(ResponseInterface $response): stdClass
    {
        $responseData = json_decode((string) $response->getBody());

        return ($responseData instanceof stdClass) ? $responseData : (object) [];
    }

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
