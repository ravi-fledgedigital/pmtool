<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationFactory;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\ResponseException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\ResponseOperationFactory;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for @see ResponseOperationFactory
 */
class ResponseOperationFactoryTest extends TestCase
{
    /**
     * @var OperationFactory|MockObject
     */
    private OperationFactory|MockObject $operationFactoryMock;

    /**
     * @var ResponseInterface|MockObject
     */
    private ResponseInterface|MockObject $responseMock;

    /**
     * @var Hook|MockObject
     */
    private Hook|MockObject $hookMock;

    /**
     * @var ResponseOperationFactory
     */
    private ResponseOperationFactory $responseOperationFactory;

    protected function setUp(): void
    {
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->hookMock = $this->createMock(Hook::class);
        $this->operationFactoryMock = $this->createMock(OperationFactory::class);
        $this->responseOperationFactory = new ResponseOperationFactory(
            $this->operationFactoryMock,
            new Json()
        );
    }

    public function testNotSuccessResponse()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Response status is not success: 500, some reason');

        $this->responseMock->expects(self::exactly(2))
            ->method('getStatusCode')
            ->willReturn(500);
        $this->responseMock->expects(self::once())
            ->method('getReasonPhrase')
            ->willReturn('some reason');
        $this->responseMock->expects(self::never())
            ->method('getBody');
        $this->operationFactoryMock->expects(self::never())
            ->method('create');
        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testNoOperationsInResponse()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('The response must contain at least one operation.');

        $this->setResponseJson('{}');

        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testMissedOperationValueInASingleOperationResponse()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('The response has a wrong format. Unable to read operation value.');

        $this->setResponseJson('{"message": "test"}');

        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testWrongJsonFormat()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Unable to parse response');

        $this->setResponseJson('{]');

        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testExceptionOperationNotRegistered()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Can not process the response: Operation "not-registered" is not registered');

        $this->setResponseJson('{"message": "test", "op": "not-registered"}');

        $this->operationFactoryMock->expects(self::once())
            ->method('create')
            ->with('not-registered', $this->hookMock)
            ->willThrowException(new NotFoundException(__('Operation "not-registered" is not registered')));

        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testExceptionOperationConfigurationNotValid()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Can not process the response: The required parameters are missed in the webhook response: path'
        );

        $this->setResponseJson('{"value": "test", "op": "add"}');

        $this->operationFactoryMock->expects(self::once())
            ->method('create')
            ->with('add', $this->hookMock)
            ->willThrowException(
                new InvalidArgumentException(__('The required parameters are missed in the webhook response: path'))
            );

        $this->responseOperationFactory->create($this->responseMock, $this->hookMock);
    }

    public function testSuccessSingleOperationResponse()
    {
        $this->setResponseJson('{"message": "test", "op": "exception"}');

        $operationMock = $this->createMock(OperationInterface::class);

        $this->operationFactoryMock->expects(self::once())
            ->method('create')
            ->with('exception', $this->hookMock)
            ->willReturn($operationMock);

        self::assertEquals(
            [$operationMock],
            $this->responseOperationFactory->create($this->responseMock, $this->hookMock)
        );
    }

    public function testSuccessMultipleOperationResponse()
    {
        $this->setResponseJson(
            '[{"value": "test", "op": "add"}, {"value": "test2", "op": "add"}, {"value": "test3", "op": "replace"}]'
        );

        $operationAddMockOne = $this->createMock(OperationInterface::class);
        $operationAddMockTwo = $this->createMock(OperationInterface::class);
        $operationReplaceMock = $this->createMock(OperationInterface::class);

        $this->operationFactoryMock->expects(self::exactly(3))
            ->method('create')
            ->willReturnCallback(
                function (
                    string $name,
                    Hook $hook
                ) use (
                    $operationAddMockOne,
                    $operationAddMockTwo,
                    $operationReplaceMock
                ) {
                    static $count = 0;
                    self::assertEquals($this->hookMock, $hook);
                    switch ($count++) {
                        case 0:
                            self::assertEquals('add', $name);
                            return $operationAddMockOne;
                        case 1:
                            self::assertEquals('add', $name);
                            return $operationAddMockTwo;
                        case 2:
                            self::assertEquals('replace', $name);
                            return $operationReplaceMock;
                    };
                    return null;
                }
            );

        self::assertEquals(
            [$operationAddMockOne, $operationAddMockTwo, $operationReplaceMock],
            $this->responseOperationFactory->create($this->responseMock, $this->hookMock)
        );
    }

    /**
     * Sets response json string
     *
     * @param string $json
     * @return void
     */
    private function setResponseJson(string $json): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->expects(self::once())
            ->method('getContents')
            ->willReturn($json);
        $this->responseMock->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->responseMock->expects(self::once())
            ->method('getBody')
            ->willReturn($bodyMock);
    }
}
