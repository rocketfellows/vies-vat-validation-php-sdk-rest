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
        // TODO: implement
    }
}
