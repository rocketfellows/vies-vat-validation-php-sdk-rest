<?php

namespace rocketfellows\ViesVatValidationRest;

use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;

abstract class AbstractVatNumberValidationRestService implements VatNumberValidationServiceInterface
{
    public function validateVat(VatNumber $vatNumber): VatNumberValidationResult
    {
        // TODO: Implement validateVat() method.
    }
}
