<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Catalog\Pricing\Render;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Plugin\Catalog\Pricing\Render\PriceBox;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Pricing\Render\PriceBox as PriceBoxSubject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PriceBox plugin
 */
class PriceBoxTest extends TestCase
{
    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var PriceBox
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = (new ObjectManager($this))->getObject(PriceBox::class, ['metadataPool' => $this->metadataPool]);
    }

    public function testAfterGetCacheKey()
    {
        $linkId = 2;
        $linkField = 'row_id';
        $saleableItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saleableItem->expects($this->once())
            ->method('getData')
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$linkField] => $linkId
            });
        /** @var PriceBoxSubject|MockObject $subject */
        $subject = $this->getMockBuilder(PriceBoxSubject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->once())
            ->method('getSaleableItem')
            ->willReturn($saleableItem);

        $entityMetadata = $this->getMockBuilder(EntityMetadata::class)
            ->onlyMethods(['getLinkField'])
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);

        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturnCallback(fn($param) => match ([$param]) {
                [ProductInterface::class] => $entityMetadata
            });
        $argumentResult = 'price-box-3';
        $expectedResult = "{$argumentResult}-{$linkId}";

        $result = $this->plugin->afterGetCacheKey($subject, $argumentResult);

        $this->assertEquals($expectedResult, $result);
    }
}
