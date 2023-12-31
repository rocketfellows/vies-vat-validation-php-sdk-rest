<?php

namespace rocketfellows\ViesVatValidationRest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use rocketfellows\ViesVatValidationInterface\exceptions\ServiceRequestException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResultFactory;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\helpers\RequestFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseErrorFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseFactory;
use stdClass;

abstract class AbstractVatNumberValidationRestService implements VatNumberValidationServiceInterface
{
    private $client;
    private $faultCodeExceptionFactory;
    private $vatNumberValidationResultFactory;

    public function __construct(
        Client $client,
        FaultCodeExceptionFactory $faultCodeExceptionFactory,
        VatNumberValidationResultFactory $vatNumberValidationResultFactory
    ) {
        $this->client = $client;
        $this->faultCodeExceptionFactory = $faultCodeExceptionFactory;
        $this->vatNumberValidationResultFactory = $vatNumberValidationResultFactory;
    }

    abstract protected function getUrl(): string;

    public function validateVat(VatNumber $vatNumber): VatNumberValidationResult
    {
        try {
            $responseData = ResponseFactory::getResponseData(
                $this->client->post(...$this->getRequestParams($vatNumber))
            );

            if (ResponseErrorFactory::isResponseWithError($responseData)) {
                throw $this->faultCodeExceptionFactory->create(...$this->getResponseErrorData($responseData));
            }

            return $this->vatNumberValidationResultFactory->createFromObject($responseData);
        } catch (ClientException | ServerException $exception) {
            throw $this->faultCodeExceptionFactory->create(
                ...$this->getResponseErrorData(ResponseFactory::getResponseData($exception->getResponse()))
            );
        } catch (GuzzleException $exception) {
            throw new ServiceRequestException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function getRequestParams(VatNumber $vatNumber): array
    {
        return [
            $this->getUrl(),
            [
                'json' => RequestFactory::getCheckVatNumberRequestData($vatNumber),
            ]
        ];
    }

    private function getResponseErrorData(stdClass $responseData): array
    {
        return [
            ResponseErrorFactory::getResponseErrorCode($responseData),
            ResponseErrorFactory::getResponseErrorMessage($responseData)
        ];
    }
}
