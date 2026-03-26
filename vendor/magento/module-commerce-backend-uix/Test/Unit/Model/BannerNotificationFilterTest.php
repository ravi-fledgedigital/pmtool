<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Test\Unit\Model;

use Magento\CommerceBackendUix\Model\BannerNotificationFilter;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use PHPUnit\Framework\TestCase;

/**
 * Unit test class for BannerNotificationFilter class
 */
class BannerNotificationFilterTest extends TestCase
{
    /**
     * @var BannerNotificationFilter
     */
    private BannerNotificationFilter $bannerNotificationFilter;

    /**
     * @var Cache
     */
    private Cache $cacheMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->bannerNotificationFilter = new BannerNotificationFilter($this->cacheMock);
    }

    /**
     * Test getMassActionBannerNotification with non array grid type
     */
    public function testGetMassActionBannerNotificationWithNonArrayGridType(): void
    {
        $this->cacheMock
            ->method('getBannerNotifications')
            ->willReturn(['massActions' => ['gridType' => 'not an array']]);
        $this->assertEquals([], $this->bannerNotificationFilter
            ->getMassActionBannerNotification('gridType', 'actionId'));
    }

    /**
     * Test getMassActionBannerNotification with missing grid type
     */
    public function testGetMassActionBannerNotificationWithMissingActionId(): void
    {
        $this->cacheMock
            ->method('getBannerNotifications')
            ->willReturn(['massActions' => ['gridType' => [['notActionId' => 'actionId']]]]);
        $this->assertEquals([], $this->bannerNotificationFilter
            ->getMassActionBannerNotification('gridType', 'actionId'));
    }

    /**
     * Test getMassActionBannerNotification with valid data
     */
    public function testGetMassActionBannerNotificationWithValidData(): void
    {
        $this->cacheMock
            ->method('getBannerNotifications')
            ->willReturn(['massActions' => ['gridType' => [['actionId' => 'actionId1']]]]);
        $this->assertEquals(['actionId' => 'actionId1'], $this->bannerNotificationFilter
            ->getMassActionBannerNotification('gridType', 'actionId1'));
    }
}
