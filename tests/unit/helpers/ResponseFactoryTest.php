<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\TestCase;

/**
 * @group vies-vat-validation-rest-helpers
 */
class ResponseFactoryTest extends TestCase
{
    public function getVatNumberValidationResultProvidedData(): array
    {
        return [
            [
                'responseData',
                'expectedVatNumberValidationResult',
            ],
        ];
    }
}
