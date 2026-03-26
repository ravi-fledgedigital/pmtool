<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Import;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var MockObject
     */
    protected $configMock;

    /**
     * @var Import|MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $this->subject = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param bool $isScheduled
     * @param int $countInvalidate
     * @return void
     * @dataProvider indexerDataProvider
     */
    public function testAfterImportSourceWhenCatalogPermissionsEnabled(bool $isScheduled, int $countInvalidate)
    {
        $this->configMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $indexer = $this->getMockBuilder(
            Indexer::class
        )->disableOriginalConstructor()
            ->getMock();
        $indexer->expects($this->exactly(2))->method('isScheduled')->willReturn($isScheduled);
        $indexer->expects($this->exactly($countInvalidate))->method('invalidate');

        $indexerRegistryMock = $this->createPartialMock(IndexerRegistry::class, ['get']);
        $indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [Category::INDEXER_ID, $indexer],
                [Product::INDEXER_ID, $indexer],
            ]);

        /**
         * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Import $import
         */
        $import = $this->objectManager->getObject(
            \Magento\CatalogPermissions\Model\Indexer\Plugin\Import::class,
            [
                'config' => $this->configMock,
                'indexerRegistry' => $indexerRegistryMock
            ]
        );
        $this->assertEquals('import', $import->afterImportSource($this->subject, 'import'));
    }

    /**
     * @return array
     */
    public function indexerDataProvider() : array
    {
        return [
            [true, 0],
            [false, 2]
        ];
    }

    public function testAfterImportSourceWhenCatalogPermissionsDisabled()
    {
        $this->configMock->expects($this->once())->method('isEnabled')->willReturn(false);

        /**
         * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Import $import
         */
        $import = $this->objectManager->getObject(
            \Magento\CatalogPermissions\Model\Indexer\Plugin\Import::class,
            [
                'config' => $this->configMock,
            ]
        );
        $this->assertEquals('import', $import->afterImportSource($this->subject, 'import'));
    }
}
