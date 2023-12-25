<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use stdClass;

class ResponseErrorFactory
{
    public static function isResponseWithError(stdClass $responseData): bool
    {
        return !is_null(self::getResponseErrorWrappers($responseData));
    }

    public static function getResponseErrorCode(stdClass $responseData): ?string
    {
        $errorData = self::getResponseErrorData($responseData);

        return !is_null($errorData) ? ($errorData->error ?? null) : null;
    }

    public static function getResponseErrorMessage(stdClass $responseData): ?string
    {
        $errorData = self::getResponseErrorData($responseData);

        return !is_null($errorData) ? ($errorData->message ?? null) : null;
    }

    private static function getResponseErrorData(stdClass $responseData): ?stdClass
    {
        $errorWrappers = self::getResponseErrorWrappers($responseData);

        if (is_null($errorWrappers)) {
            return null;
        }

        if (empty($errorWrappers)) {
            return null;
        }

        $error = $errorWrappers[0] ?? null;

        if (empty($error)) {
            return null;
        }

        return $error;
    }

    private static function getResponseErrorWrappers(stdClass $responseData): ?array
    {
        $errorWrappers = $responseData->errorWrappers ?? null;

        if (is_null($errorWrappers)) {
            return null;
        }

        return is_array($errorWrappers) ? $errorWrappers : null;
    }
}
