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
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\helpers\RequestFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseErrorFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseFactory;

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

    abstract protected function getUrl(): string;

    public function validateVat(VatNumber $vatNumber): VatNumberValidationResult
    {
        try {
            $responseData = ResponseFactory::getResponseData(
                $this->client->post(
                    $this->getUrl(),
                    [
                        'json' => RequestFactory::getCheckVatNumberRequestData($vatNumber),
                    ]
                )
            );

            // TODO: check $responseData on emptiness with rocketfellows\ViesVatValidationRest\helpers\ResponseFactory::isResponseDataEmpty
            // TODO: if $responseData empty - throw rocketfellows\ViesVatValidationInterface\exceptions\ServiceRequestException

            if (ResponseErrorFactory::isResponseWithError($responseData)) {
                throw $this->faultCodeExceptionFactory->create(
                    ResponseErrorFactory::getResponseErrorCode($responseData),
                    ResponseErrorFactory::getResponseErrorMessage($responseData)
                );
            }

            // TODO: think about check response signature, may be should throw exception if there is not such set of expected attributes
            return ResponseFactory::getVatNumberValidationResult($responseData);
        } catch (ClientException | ServerException $exception) {
            $exceptionResponseData = ResponseFactory::getResponseData($exception->getResponse());

            throw $this->faultCodeExceptionFactory->create(
                ResponseErrorFactory::getResponseErrorCode($exceptionResponseData),
                ResponseErrorFactory::getResponseErrorMessage($exceptionResponseData)
            );
        } catch (GuzzleException $exception) {
            throw new ServiceRequestException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}
