<?php

namespace rocketfellows\ViesVatValidationRest\helpers;

use stdClass;

class ResponseErrorFactory
{
    private const RESPONSE_DATA_PROPERTY_ERROR_WRAPPERS = 'errorWrappers';

    private const EMPTY_ERROR_CODE = '';
    private const EMPTY_ERROR_MESSAGE = '';

    public static function isResponseWithError(stdClass $responseData): bool
    {
        return property_exists($responseData, self::RESPONSE_DATA_PROPERTY_ERROR_WRAPPERS);
    }

    public static function getResponseErrorCode(stdClass $responseData): string
    {
        $errorData = self::getResponseErrorData($responseData);

        return !is_null($errorData) ? ($errorData->error ?? self::EMPTY_ERROR_CODE) : self::EMPTY_ERROR_CODE;
    }

    public static function getResponseErrorMessage(stdClass $responseData): string
    {
        $errorData = self::getResponseErrorData($responseData);

        return !is_null($errorData) ? ($errorData->message ?? self::EMPTY_ERROR_MESSAGE) : self::EMPTY_ERROR_MESSAGE;
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
