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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Webhook\HookHeader;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverComposite;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookHeaderResolverComposite
 */
class HookHeaderResolverCompositeTest extends TestCase
{
    public function testResolve()
    {
        $resolver1 = $this->createMock(HookHeaderResolverInterface::class);
        $resolver2 = $this->createMock(HookHeaderResolverInterface::class);
        $resolver3 = $this->createMock(HookHeaderResolverInterface::class);

        $hook = $this->createMock(Hook::class);
        $hookData = [];

        $resolver1->expects(self::once())
            ->method('resolve')
            ->with($hook, $hookData)
            ->willReturn([
                'header1' => 'value1',
                'header2' => 'value2',
            ]);
        $resolver2->expects(self::once())
            ->method('resolve')
            ->with($hook, $hookData)
            ->willReturn([
                'header3' => 'value3',
            ]);
        $resolver3->expects(self::once())
            ->method('resolve')
            ->with($hook, $hookData)
            ->willReturn([
                'header4' => 'value4',
                'header5' => 'value5',
            ]);

        $composite = new HookHeaderResolverComposite([$resolver1, $resolver2, $resolver3]);

        self::assertEquals(
            [
                'header1' => 'value1',
                'header2' => 'value2',
                'header3' => 'value3',
                'header4' => 'value4',
                'header5' => 'value5',
            ],
            $composite->resolve($hook, $hookData)
        );
    }
}
