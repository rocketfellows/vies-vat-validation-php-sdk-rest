<?php

namespace rocketfellows\ViesVatValidationRest\services;

use GuzzleHttp\Client;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationRest\AbstractVatNumberValidationRestService;

class VatNumberValidationRestExpansibleService extends AbstractVatNumberValidationRestService
{
    private $url;

    public function __construct(
        string $url,
        Client $client,
        FaultCodeExceptionFactory $faultCodeExceptionFactory
    ) {
        parent::__construct($client, $faultCodeExceptionFactory);

        $this->url = $url;
    }

    protected function getUrl(): string
    {
        return $this->url;
    }
}
