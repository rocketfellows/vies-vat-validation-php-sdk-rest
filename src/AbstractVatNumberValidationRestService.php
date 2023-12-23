<?php

namespace rocketfellows\ViesVatValidationRest;

use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use stdClass;

abstract class AbstractVatNumberValidationRestService implements VatNumberValidationServiceInterface
{
    private $client;
    private $faultCodeExceptionFactory;

    public function __construct(
        Client $client,
        FaultCodeExceptionFactory $faultCodeExceptionFactory
    ) {
        $this->client = $client;
        $this->faultCodeExceptionFactory = $faultCodeExceptionFactory;
    }

    public function validateVat(VatNumber $vatNumber): VatNumberValidationResult
    {
        // TODO: Implement validateVat() method.
    }

    private function isRequestFaulted(stdClass $responseData): bool
    {
        return !empty($this->getResponseErrorCode($responseData));
    }

    private function getResponseErrorCode(stdClass $responseData): ?string
    {
        $errorWrapper = $responseData->errorWrappers ?? [];

        if (!is_array($errorWrapper)) {
            return null;
        }

        if (empty($errorWrapper)) {
            return null;
        }

        $error = $errorWrapper[0] ?? null;

        if (empty($error)) {
            return null;
        }

        return $error->error ?? null;
    }
}
