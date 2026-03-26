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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Webhook\AllowList;

use Magento\AdobeCommerceWebhooks\Model\Webhook\AllowList\AllowedChecker;
use Magento\AdobeCommerceWebhooks\Model\Webhook\AllowList\AllowedListInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AllowedCheckerTest extends TestCase
{
    /**
     * @var AllowedListInterface|MockObject
     */
    private AllowedListInterface|MockObject $allowedListMock;

    /**
     * @var AllowedChecker
     */
    private AllowedChecker $allowedChecker;

    protected function setUp(): void
    {
        $this->allowedListMock = $this->createMock(AllowedListInterface::class);

        $this->allowedChecker = new AllowedChecker($this->allowedListMock);
    }

    public function testIsAllowed(): void
    {
        $this->allowedListMock->expects(self::exactly(6))
            ->method('getList')
            ->willReturn(['hook1', 'hook2']);

        self::assertTrue($this->allowedChecker->isAllowed('hook1'));
        self::assertTrue($this->allowedChecker->isAllowed('hook2'));
        self::assertFalse($this->allowedChecker->isAllowed('hook3'));
    }

    public function testIsAllowedEmptyList(): void
    {
        $this->allowedListMock->expects(self::exactly(2))
            ->method('getList')
            ->willReturn([]);

        self::assertTrue($this->allowedChecker->isAllowed('hook1'));
        self::assertTrue($this->allowedChecker->isAllowed('hook2'));
    }
}
