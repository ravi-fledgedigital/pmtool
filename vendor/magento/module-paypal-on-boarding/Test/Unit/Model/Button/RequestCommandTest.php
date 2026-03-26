<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Laminas\Http\Response;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\PaypalOnBoarding\Model\Button\ResponseValidator;
use Magento\PaypalOnBoarding\Model\Button\RequestCommand;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestCommandTest
 */
class RequestCommandTest extends TestCase
{
    /**
     * @var CurlFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private CurlFactory $clientFactoryMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var ResponseValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private ResponseValidator $responseButtonValidatorMock;

    /**
     * @var RequestCommand
     */
    private RequestCommand $requestCommand;

    /**
     * @var array $requestParams
     */
    private array $requestParams = [
        'countryCode' => 'UK',
        'magentoMerchantId' => 'qwe-rty',
        'successUrl' => 'https://magento.loc/paypal_onboarding/redirect/success',
        'failureUrl' => 'https://magento.loc/paypal_onboarding/redirect/failure'
    ];

    /**
     * @var string
     */
    private string $host = 'https://middleman.com/start';

    /**
     * @var array $responseFields
     */
    private array $responseFields = ['liveButtonUrl', 'sandboxButtonUrl'];

    protected function setUp(): void
    {
        $this->clientFactoryMock = $this->createMock(CurlFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseButtonValidatorMock = $this->createMock(ResponseValidator::class);

        $this->requestCommand = new RequestCommand(
            $this->clientFactoryMock,
            $this->responseButtonValidatorMock,
            $this->loggerMock
        );
    }

    /**
     * Test successful request
     *
     * @covers \Magento\PaypalOnBoarding\Model\Button\RequestCommand::execute()
     */
    public function testExecuteSuccess(): void
    {

        $liveButtonUrl = "https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $sandboxButtonUrl = "https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $middlemanResponse = json_encode(['liveButtonUrl' => $liveButtonUrl, 'sandboxButtonUrl' => $sandboxButtonUrl]);

        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);

        $httpClient->expects(static::once())
            ->method('get')
            ->with($this->host . '?' . http_build_query($this->requestParams));
        $httpClient->expects(static::once())
            ->method('getStatus')
            ->willReturn(200);
        $httpClient->expects(static::once())
            ->method('getBody')
            ->willReturn($middlemanResponse);

        $this->responseButtonValidatorMock->expects(static::once())
            ->method('validate')
            ->with($this->isInstanceOf(Response::class), $this->responseFields)
            ->willReturn(true);

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Request fails due to RuntimeException
     */
    public function testExecuteWithHttpClientException(): void
    {
        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);
        $httpClient->expects(static::once())
            ->method('get')
            ->with($this->host . '?' . http_build_query($this->requestParams))
            ->willThrowException(new \Exception('Connection error'));

        $this->responseButtonValidatorMock->expects(static::never())
            ->method('validate');

        $this->loggerMock->expects(static::once())
            ->method('error');

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Request fails due to ValidatorException
     */
    public function testExecuteWithValidatorException(): void
    {
        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);

        $httpClient->expects(static::once())
            ->method('get')
            ->with($this->host . '?' . http_build_query($this->requestParams));
        $httpClient->expects(static::once())
            ->method('getStatus')
            ->willReturn(200);
        $httpClient->expects(static::once())
            ->method('getBody')
            ->willReturn(json_encode(['sandboxButtonUrl' => 'sandboxUrl', 'liveButtonUrl' => 'liveUrl']));

        $this->responseButtonValidatorMock->expects(static::once())
            ->method('validate')
            ->with($this->isInstanceOf(Response::class), $this->responseFields)
            ->willThrowException(new ValidatorException(__('error')));

        $this->loggerMock->expects(static::once())
            ->method('error');

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Return Curl mock
     *
     * @return Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getHttpClientMock(): Curl
    {
        /** @var Curl|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'getStatus', 'getBody'])
            ->getMock();

        return $httpClient;
    }
}
