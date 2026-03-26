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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\VariablesResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\HeaderResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\Resolver\HookHeaderResolver;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\ResolverFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookHeaderResolver
 */
class HookHeaderResolverTest extends TestCase
{
    /**
     * @var ResolverFactory|MockObject
     */
    private ResolverFactory|MockObject $resolverFactoryMock;

    /**
     * @var VariablesResolverInterface|MockObject
     */
    private VariablesResolverInterface|MockObject $variablesResolverMock;

    /**
     * @var RequestIdInterface|MockObject
     */
    private RequestIdInterface|MockObject $requestIdMock;

    /**
     * @var ContextPool|MockObject
     */
    private ContextPool|MockObject $contextPoolMock;

    /**
     * @var ContextRetriever|MockObject
     */
    private ContextRetriever|MockObject $contextRetrieverMock;

    /**
     * @var HookHeaderResolver
     */
    private HookHeaderResolver $hookHeaderResolver;

    protected function setUp(): void
    {
        $this->resolverFactoryMock = $this->createMock(ResolverFactory::class);
        $this->variablesResolverMock = $this->createMock(VariablesResolverInterface::class);
        $this->requestIdMock = $this->createMock(RequestIdInterface::class);
        $this->requestIdMock->expects(self::once())
            ->method('get')
            ->willReturn('request-id');
        $this->contextPoolMock = $this->createMock(ContextPool::class);
        $this->contextRetrieverMock = $this->createMock(ContextRetriever::class);

        $this->hookHeaderResolver = new HookHeaderResolver(
            $this->resolverFactoryMock,
            $this->variablesResolverMock,
            $this->requestIdMock,
            $this->contextPoolMock,
            $this->contextRetrieverMock
        );
    }

    public function testResolve()
    {
        $hookHeaderOneMock = $this->createMock(HookHeader::class);
        $hookHeaderOneMock->expects(self::once())
            ->method('getResolver')
            ->willReturn(null);
        $hookHeaderOneMock->expects(self::once())
            ->method('getValue')
            ->willReturn('value1');
        $hookHeaderOneMock->expects(self::once())
            ->method('getName')
            ->willReturn('hook1');
        $hookHeaderTwoMock = $this->createMock(HookHeader::class);
        $hookHeaderTwoMock->expects(self::exactly(2))
            ->method('getResolver')
            ->willReturn('resolverValue');
        $hookHeaderTwoMock->expects(self::never())
            ->method('getValue');
        $hookHeaderTwoMock->expects(self::never())
            ->method('getName');
        $hookMock = $this->createMock(Hook::class);
        $hookMock->expects(self::once())
            ->method('getHeaders')
            ->willReturn([
                $hookHeaderOneMock,
                $hookHeaderTwoMock
            ]);
        $hookData = ['data' => 'test'];

        $headerResolverMock = $this->createMock(HeaderResolverInterface::class);
        $headerResolverMock->expects(self::once())
            ->method('getHeaders')
            ->willReturn(['header1' => 'resolvedHeader1']);
        $this->resolverFactoryMock->expects(self::once())
            ->method('create')
            ->with('resolverValue')
            ->willReturn($headerResolverMock);
        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('value1')
            ->willReturn(false);
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');
        $this->variablesResolverMock->expects(self::once())
            ->method('resolve')
            ->willReturn('resolvedValue');

        $expected = [
            'hook1' => 'resolvedValue',
            'header1' => 'resolvedHeader1',
            'x-adobe-commerce-request-id' => 'request-id',
        ];
        $this->assertEquals($expected, $this->hookHeaderResolver->resolve($hookMock, $hookData));
    }

    public function testResolveEmptyHeaders()
    {
        $hookMock = $this->createMock(Hook::class);
        $hookMock->expects(self::once())
            ->method('getHeaders')
            ->willReturn([]);
        $hookData = ['data' => 'test'];

        $this->resolverFactoryMock->expects(self::never())
            ->method('create');
        $this->contextPoolMock->expects(self::never())
            ->method('has');
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');
        $this->variablesResolverMock->expects(self::never())
            ->method('resolve');

        $this->assertEquals(
            ['x-adobe-commerce-request-id' => 'request-id'],
            $this->hookHeaderResolver->resolve($hookMock, $hookData)
        );
    }

    public function testResolveWithContextValue()
    {
        $hookHeaderMock = $this->createMock(HookHeader::class);
        $hookHeaderMock->expects(self::once())
            ->method('getResolver')
            ->willReturn(null);
        $hookHeaderMock->expects(self::once())
            ->method('getValue')
            ->willReturn('context_test.get_value');
        $hookHeaderMock->expects(self::once())
            ->method('getName')
            ->willReturn('header1');

        $hookMock = $this->createMock(Hook::class);
        $hookMock->expects(self::once())
            ->method('getHeaders')
            ->willReturn([$hookHeaderMock]);
        $hookData = ['data' => 'test'];

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_test')
            ->willReturn(true);
        $this->contextRetrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with('context_test.get_value', $hookMock)
            ->willReturn('retrieved_value');

        $this->resolverFactoryMock->expects(self::never())
            ->method('create');
        $this->variablesResolverMock->expects(self::never())
            ->method('resolve');

        $expected = [
            'header1' => 'retrieved_value',
            'x-adobe-commerce-request-id' => 'request-id',
        ];
        $this->assertEquals($expected, $this->hookHeaderResolver->resolve($hookMock, $hookData));
    }
}
