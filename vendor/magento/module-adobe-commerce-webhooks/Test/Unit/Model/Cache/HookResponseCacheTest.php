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

use Magento\AdobeCommerceWebhooks\Model\Cache\HookResponseCache;
use Magento\AdobeCommerceWebhooks\Model\Cache\KeyGeneratorInterface;
use Magento\AdobeCommerceWebhooks\Model\Cache\Type\WebhookResponse;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Test for @see HookResponseCache
 */
class HookResponseCacheTest extends TestCase
{
    /**
     * @var CacheInterface|MockObject
     */
    private CacheInterface|MockObject $cacheMock;

    /**
     * @var KeyGeneratorInterface|MockObject
     */
    private KeyGeneratorInterface|MockObject $keyGeneratorMock;

    /**
     * @var  LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var HookResponseCache
     */
    private HookResponseCache $hookResponseCache;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->keyGeneratorMock = $this->createMock(KeyGeneratorInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->hookResponseCache = new HookResponseCache(
            $this->cacheMock,
            $this->keyGeneratorMock,
            $this->loggerMock
        );
    }

    public function testSaveResponse(): void
    {
        $requestParamsMock = $this->createMock(RequestParams::class);
        $hookMock = $this->createMock(Hook::class);
        $hookResponse = 'testResponse';
        $ttl = 3600;

        $hookMock->expects(self::once())
            ->method('getTtl')
            ->willReturn($ttl);
        $this->keyGeneratorMock->expects(self::once())
            ->method('generate')
            ->willReturn('testKey');
        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with(
                $hookResponse,
                WebhookResponse::TYPE_IDENTIFIER . '_testKey',
                [WebhookResponse::CACHE_TAG],
                $ttl
            );

        $this->hookResponseCache->saveResponse($requestParamsMock, $hookResponse, $hookMock);
    }

    public function testGetResponse(): void
    {
        $requestParamsMock = $this->createMock(RequestParams::class);
        $hookMock = $this->createMock(Hook::class);

        $this->keyGeneratorMock->expects(self::once())
            ->method('generate')
            ->willReturn('testKey');
        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with(WebhookResponse::TYPE_IDENTIFIER . '_testKey')
            ->willReturn('testResponse');

        self::assertEquals(
            'testResponse',
            $this->hookResponseCache->getResponse($requestParamsMock, $hookMock)
        );
    }

    public function testGetResponseWithTemporaryCacheOnly(): void
    {
        $requestParamsMock = $this->createMock(RequestParams::class);
        $hookMock = $this->createMock(Hook::class);

        $this->keyGeneratorMock->expects(self::exactly(2))
            ->method('generate')
            ->willReturn('testKey');
        $this->cacheMock->expects(self::never())
            ->method('load');

        $this->hookResponseCache->saveResponse($requestParamsMock, 'testResponse', $hookMock);

        self::assertEquals(
            'testResponse',
            $this->hookResponseCache->getResponse($requestParamsMock, $hookMock)
        );
    }
}
