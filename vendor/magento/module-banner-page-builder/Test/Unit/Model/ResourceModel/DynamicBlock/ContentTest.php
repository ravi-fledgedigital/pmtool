<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerPageBuilder\Test\Unit\Model\ResourceModel\DynamicBlock;

use Magento\Banner\Model\ResourceModel\Banner;
use Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Content;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    /**
     * @var Banner|MockObject
     */
    private $bannerFactory;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var Select|MockObject
     */
    private $dbSelect;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->bannerFactory = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->bannerFactory->method('getConnection')->willReturn($this->connection);
        $this->dbSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     *
     * @dataProvider dataProvider
     * @param bool|string $select
     * @return void
     */
    public function testGetById(bool|string $select): void
    {
        $this->connection->method('select')->willReturn($this->dbSelect);
        $this->dbSelect->method('from')->willReturnSelf();
        $this->dbSelect->method('where')->willReturn('test');
        $this->connection->method('fetchOne')->willReturn($select);
        $content = new Content($this->bannerFactory);
        $blockId = 1;
        $result = $content->getById($blockId);
        $this->assertIsNotBool($result);
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            [false],[""],["testHTML"]
        ];
    }
}
