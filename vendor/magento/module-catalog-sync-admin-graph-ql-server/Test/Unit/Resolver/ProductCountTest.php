<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdminGraphQlServer\Test\Unit\Resolver;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogSyncAdminGraphQlServer\Resolver\Query\ProductCount;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see ProductCount
 */
class ProductCountTest extends TestCase
{
    /**
     * @var ProductCount
     */
    private ProductCount $productCount;

    /**
     * @var CollectionFactory|MockObject
     */
    private CollectionFactory $collectionFactoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private Collection $productCollectionMock;

    /**
     * @var Field|MockObject
     */
    private Field $fieldMock;

    /**
     * @var ResolveInfo|MockObject
     */
    private ResolveInfo $resolveInfoMock;

    /**
     * @var Context|MockObject
     */
    private Context $contextMock;

    /**
     * @var array
     */
    private array $valueMock = [];

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->resolveInfoMock = $this->createMock(ResolveInfo::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->productCollectionMock = $this->createMock(Collection::class);
        $this->productCount = new ProductCount(
            $this->collectionFactoryMock
        );

    }

    public function testGetProductCount(): void {
        $this->setUp();
        $args = [];
        $this->collectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock
            ->expects($this->never())
            ->method('addStoreFilter');
        $this->productCollectionMock
            ->expects($this->never())
            ->method('addWebsiteFilter');
        $this->productCollectionMock
            ->expects($this->never())
            ->method('addAttributeToFilter');
        $this->productCount->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            $this->valueMock,
            $args
        );
    }

    public function testGetProductCountByStoreView(): void {
        $this->setUp();
        $args = [
            'productCountRequest' => [
                'filters' => [],
                'storeViewCode' => 'testStore'
            ]
        ];
        $this->collectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addStoreFilter')
            ->with('testStore');
        $this->productCount->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            $this->valueMock,
            $args
        );
    }

    public function testGetProductCountByWebsite(): void {
        $this->setUp();
        $args = [
            'productCountRequest' => [
                'filters' => [],
                'websiteCode' => 'testWebsite'
            ]
        ];
        $this->collectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addWebsiteFilter')
            ->with('testWebsite');
        $this->productCount->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            $this->valueMock,
            $args
        );
    }

    public function testGetProductCountByVisibility(): void {
        $this->setUp();
        $args = [
            'productCountRequest' => [
                'filters' => [
                    'visibility'=> ['CATALOG']
                ],
                'storeViewCode' => 'testStore'
            ]
        ];
        $this->collectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('visibility', array('in' => array(Visibility::VISIBILITY_IN_CATALOG)));
        $this->productCount->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            $this->valueMock,
            $args
        );
    }

    public function testGetProductCountOfEnabledProducts(): void {
        $this->setUp();
        $args = [
            'productCountRequest' => [
                'filters' => [
                    'enabled'=> true
                ],
                'storeViewCode' => 'testStore'
            ]
        ];
        $this->collectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('status', array('eq' => Status::STATUS_ENABLED));
        $this->productCount->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            $this->valueMock,
            $args
        );
    }
}
