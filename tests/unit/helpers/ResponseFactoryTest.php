<?php

namespace rocketfellows\ViesVatValidationRest\tests\unit\helpers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use rocketfellows\ViesVatValidationRest\helpers\ResponseFactory;
use stdClass;

/**
 * @group vies-vat-validation-rest-helpers
 */
class ResponseFactoryTest extends TestCase
{
    /**
     * @dataProvider getResponseDataEmptinessCheckProvidedData
     */
    public function testIsResponseDataEmpty(stdClass $responseData, bool $isResponseDataEmpty): void
    {
        $this->assertEquals($isResponseDataEmpty, ResponseFactory::isResponseDataEmpty($responseData));
    }

    public function getResponseDataEmptinessCheckProvidedData(): array
    {
        return [
            'response data empty' => [
                'responseData' => (object) [],
                'isResponseDataEmpty' => true,
            ],
            'response data not empty' => [
                'responseData' => (object) ['foo'],
                'isResponseDataEmpty' => false,
            ],
        ];
    }

    /**
     * @dataProvider getResponseProvidedData
     */
    public function testGetResponseData(ResponseInterface $response, stdClass $expectedResponseData): void
    {
        $this->assertEquals($expectedResponseData, ResponseFactory::getResponseData($response));
    }

    public function getResponseProvidedData(): array
    {
        return [
            'response body empty json' => [
                'response' => $this->getResponseMock(['body' => '{}']),
                'expectedResponseData' => (object) [],
            ],
            'response body not empty' => [
                'response' => $this->getResponseMock(['body' => '{"foo": "bar", "fooBar": true, "bar": 1, "barFoo": 0, "b": ""}']),
                'expectedResponseData' => (object) [
                    'foo' => 'bar',
                    'fooBar' => true,
                    'bar' => 1,
                    'barFoo' => 0,
                    'b' => '',
                ],
            ],
            'response body empty string' => [
                'response' => $this->getResponseMock(['body' => '']),
                'expectedResponseData' => (object) [],
            ],
            'response body invalid json string' => [
                'response' => $this->getResponseMock(['body' => '{,}']),
                'expectedResponseData' => (object) [],
            ],
            'response body random string' => [
                'response' => $this->getResponseMock(['body' => 'foo123213bar']),
                'expectedResponseData' => (object) [],
            ],
            'response body int' => [
                'response' => $this->getResponseMock(['body' => '200']),
                'expectedResponseData' => (object) [],
            ],
            'response body float' => [
                'response' => $this->getResponseMock(['body' => '200.123231']),
                'expectedResponseData' => (object) [],
            ],
            'response body bool' => [
                'response' => $this->getResponseMock(['body' => 'true']),
                'expectedResponseData' => (object) [],
            ],
        ];
    }

    private function getResponseMock(array $params = []): MockObject
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($params['body'] ?? '');

        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('getBody')->willReturn($stream);

        return $mock;
    }
}
