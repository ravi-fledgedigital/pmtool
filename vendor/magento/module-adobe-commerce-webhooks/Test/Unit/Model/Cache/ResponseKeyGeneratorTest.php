<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Cache;

use Magento\AdobeCommerceWebhooks\Model\Cache\ResponseKeyGenerator;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see ResponseKeyGenerator
 */
class ResponseKeyGeneratorTest extends TestCase
{
    /**
     * @var EncryptorInterface|MockObject
     */
    protected EncryptorInterface|MockObject $encryptorInterfaceMock;

    /**
     * @var Json|MockObject
     */
    private Json|MockObject $jsonMock;

    /**
     * @var array|string[] $filteredHeaders
     */
    private array $filteredHeaders = ['testHeaderThree'];

    /**
     * @var ResponseKeyGenerator
     */
    private ResponseKeyGenerator $responseKeyGenerator;

    protected function setUp(): void
    {
        $this->encryptorInterfaceMock = $this->createMock(EncryptorInterface::class);
        $this->jsonMock = $this->createMock(Json::class);

        $this->responseKeyGenerator = new ResponseKeyGenerator(
            $this->encryptorInterfaceMock,
            $this->jsonMock,
            $this->filteredHeaders
        );
    }

    public function testGenerateCacheKey(): void
    {
        $requestParamHeaders = [
            'testHeaderOne' => 'testValue',
            'testHeaderTwo' => 'testValue',
            'testHeaderThree' => 'testValue',
            'testHeaderFour' => 'testValue'
        ];

        $requestParamsMock = $this->createMock(RequestParams::class);

        $requestParamsMock->expects(self::once())
            ->method('getHeaders')
            ->willReturn($requestParamHeaders);
        $requestHeaders = array_diff_key($requestParamHeaders, array_flip($this->filteredHeaders));
        $requestParamsMock->expects(self::once())
            ->method('getUrl')
            ->willReturn('testUrl');
        $requestParamsMock->expects(self::once())
            ->method('getBody')
            ->willReturn(['testBody']);
        $this->jsonMock->expects(self::exactly(2))
            ->method('serialize')
            ->willReturnCallback(function ($requestParams) use ($requestHeaders) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($requestHeaders, $requestParams);
                        return 'testSerializedHeaders';
                    case 1:
                        self::assertEquals(['testBody'], $requestParams);
                        return 'testSerializedBody';
                }
            });
        $this->encryptorInterfaceMock->expects(self::once())
            ->method('hash')
            ->with('testUrl_testSerializedHeaders_testSerializedBody')
            ->willReturn('test_cache_key');

        self::assertEquals('test_cache_key', $this->responseKeyGenerator->generate($requestParamsMock));
    }
}
