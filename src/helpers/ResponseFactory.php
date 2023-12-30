<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use Psr\Http\Message\ResponseInterface;
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
}
