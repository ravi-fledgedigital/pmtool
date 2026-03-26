<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Plugin\Helper;

use Magento\GoogleTagManager\Plugin\Helper\Data;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\GoogleTagManager\Helper\Data as Helper;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $model;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfigMock;

    /**
     * @var Helper
     */
    private $subjectMock;

    protected function setUp(): void
    {
        $this->tagManagerConfigMock = $this->createMock(TagManagerConfig::class);
        $this->subjectMock = $this->createMock(Helper::class);
        $this->model = new Data(
            $this->tagManagerConfigMock
        );
    }

    public function testAfterIsTagManagerAvailable()
    {
        $this->tagManagerConfigMock->expects($this->atLeastOnce())
            ->method('isTagManagerAvailable')
            ->willReturn(false);
        $actualValue = $this->model->afterIsTagManagerAvailable(
            $this->subjectMock,
            true
        );
        $expectedValue = true;
        $this->assertEquals($expectedValue, $actualValue);
    }
}
