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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Webhook\HookHeader\Resolver;

use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\AdobeCommerceWebhooks\Model\Signature\DigitalSignatureInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\Resolver\DigitalSignatureResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see DigitalSignatureResolver
 */
class DigitalSignatureResolverTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private Config|MockObject $configMock;

    /**
     * @var DigitalSignatureInterface|MockObject
     */
    private DigitalSignatureInterface|MockObject $digitalSignatureMock;

    /**
     * @var DigitalSignatureResolver
     */
    private DigitalSignatureResolver $digitalSignatureResolver;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->digitalSignatureMock = $this->createMock(DigitalSignatureInterface::class);

        $this->digitalSignatureResolver = new DigitalSignatureResolver(
            $this->configMock,
            $this->digitalSignatureMock
        );
    }

    public function testResolve()
    {
        $hook = $this->createMock(Hook::class);
        $hookData = ['data' => 'test'];

        $this->configMock->expects(self::once())
            ->method('isDigitalSignatureEnabled')
            ->willReturn(true);
        $this->configMock->expects(self::once())
            ->method('getDigitalSignaturePrivateKey')
            ->willReturn('privateKey');
        $this->digitalSignatureMock->expects($this->once())
            ->method('sign')
            ->with($hookData)
            ->willReturn('signedData');

        $expected = ['x-adobe-commerce-webhook-signature' => 'signedData'];
        $this->assertEquals($expected, $this->digitalSignatureResolver->resolve($hook, $hookData));
    }

    public function testResolveWhenDigitalSignatureIsDisabled()
    {
        $hook = $this->createMock(Hook::class);
        $hookData = ['data' => 'test'];

        $this->configMock->expects(self::once())
            ->method('isDigitalSignatureEnabled')
            ->willReturn(false);
        $this->configMock->expects(self::never())
            ->method('getDigitalSignaturePrivateKey');

        $this->assertEquals([], $this->digitalSignatureResolver->resolve($hook, $hookData));
    }
}
