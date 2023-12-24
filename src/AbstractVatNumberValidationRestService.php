<?php

namespace rocketfellows\ViesVatValidationRest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use rocketfellows\ViesVatValidationInterface\exceptions\ServiceRequestException;
use rocketfellows\ViesVatValidationInterface\FaultCodeExceptionFactory;
use rocketfellows\ViesVatValidationInterface\VatNumber;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationResult;
use rocketfellows\ViesVatValidationInterface\VatNumberValidationServiceInterface;
use rocketfellows\ViesVatValidationRest\helpers\RequestFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseErrorFactory;
use rocketfellows\ViesVatValidationRest\helpers\ResponseFactory;
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

    abstract protected function getUrl(): string;

    public function validateVat(VatNumber $vatNumber): VatNumberValidationResult
    {
        try {
            $responseData = $this->getResponseData(
                $this->client->post(
                    $this->getUrl(),
                    [
                        'json' => RequestFactory::getCheckVatNumberRequestData($vatNumber),
                    ]
                )
            );

            if (ResponseErrorFactory::isResponseWithError($responseData)) {
                throw $this->faultCodeExceptionFactory->create(
                    ResponseErrorFactory::getResponseErrorCode($responseData),
                    ResponseErrorFactory::getResponseErrorMessage($responseData)
                );
            }

            return ResponseFactory::getVatNumberValidationResult($responseData);
        } catch (ClientException | ServerException $exception) {
            $exceptionResponseData = $this->getResponseData($exception->getResponse());

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

    private function getResponseData(ResponseInterface $response): stdClass
    {
        return json_decode((string) $response->getBody());
    }
}
