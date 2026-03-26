<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as ProductPriceIndexerProcessor;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexerProcessor;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Indexer\Test\Fixture\ScheduleMode;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Test\Fixture\Update as UpdateFixture;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductStagingTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var int
     */
    private $currentVersionId;

    /**
     * @var ProductStaging
     */
    private $productStaging;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var int
     */
    private $currentStoreId;

    /**
     * @var ScopeOverriddenValue
     */
    private $scopeOverriddenValue;

    protected function setUp(): void
    {
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->versionManager = Bootstrap::getObjectManager()->get(VersionManager::class);
        $this->currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->productStaging = Bootstrap::getObjectManager()->create(ProductStaging::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->localeDate = Bootstrap::getObjectManager()->create(TimezoneInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->currentStoreId = $this->storeManager->getStore()->getId();
        $this->scopeOverriddenValue = Bootstrap::getObjectManager()->get(ScopeOverriddenValue::class);
    }

    protected function tearDown(): void
    {
        $this->versionManager->setCurrentVersionId($this->currentVersionId);
        $this->storeManager->setCurrentStore($this->currentStoreId);
    }

    #[
        DbIsolation(false),
        DataFixture(UpdateFixture::class, as: 'update1'),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ScheduleMode::class, ['indexer' => ProductPriceIndexerProcessor::INDEXER_ID]),
        DataFixture(ScheduleMode::class, ['indexer' => StockIndexerProcessor::INDEXER_ID]),
    ]
    public function testScheduleDoesNotUpdateMview(): void
    {
        $productPriceCl = Bootstrap::getObjectManager()->create(ChangelogInterface::class)
            ->setViewId(ProductPriceIndexerProcessor::INDEXER_ID);
        $productPriceVersion = $productPriceCl->getVersion();
        $stockCl = Bootstrap::getObjectManager()->create(ChangelogInterface::class)
            ->setViewId(StockIndexerProcessor::INDEXER_ID);
        $stockVersion = $stockCl->getVersion();

        $product = $this->fixtures->get('product1');
        $update = $this->fixtures->get('update1');
        $this->versionManager->setCurrentVersionId($update->getId());
        $this->productStaging->schedule($product, $update->getId());
        $this->versionManager->setCurrentVersionId($this->currentVersionId);

        self::assertEquals($productPriceVersion, $productPriceCl->getVersion());
        self::assertEquals($stockVersion, $stockCl->getVersion());
    }

    #[
        DataFixture(ProductFixture::class, ['special_price' => 9], as: 'product1')
    ]
    public function testSavingProductWithSpecialPriceShouldNotBreakInheritanceForDateAttributes(): void
    {
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);
        $product = $this->fixtures->get('product1');
        $sku = $product->getSku();
        $product = $this->productRepository->get($sku, storeId: Store::DEFAULT_STORE_ID, forceReload: true);
        $now = $this->localeDate->date(useTimezone: false)->format('Y-m-d H:i:s');
        $this->assertLessThanOrEqual(
            $now,
            $this->localeDate->convertConfigTimeToUtc($product->getSpecialFromDate())
        );
        $this->assertNull($product->getSpecialToDate());
        $this->storeManager->setCurrentStore(Store::DISTRO_STORE_ID);
        $product = $this->productRepository->get($sku, storeId: Store::DISTRO_STORE_ID, forceReload: true);
        $this->productRepository->save($product);
        $product = $this->productRepository->get($sku, storeId: Store::DISTRO_STORE_ID, forceReload: true);
        $this->assertLessThanOrEqual(
            $now,
            $this->localeDate->convertConfigTimeToUtc($product->getSpecialFromDate())
        );
        $this->assertNull($product->getSpecialToDate());
        $this->assertFalse(
            $this->scopeOverriddenValue->containsValue(
                ProductInterface::class,
                $product,
                'special_from_date',
                Store::DISTRO_STORE_ID
            )
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'product1'),
    ]
    public function testFromToDatetime(): void
    {
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);
        $product = $this->fixtures->get('product1');
        $sku = $product->getSku();
        $product = $this->productRepository->get($sku, storeId: Store::DEFAULT_STORE_ID, forceReload: true);
        $product->setSpecialPrice(9);
        $product->setIsNew(1);
        $this->productRepository->save($product);
        $product = $this->productRepository->get($sku, storeId: Store::DEFAULT_STORE_ID, forceReload: true);
        $now = $this->localeDate->date(useTimezone: false)->format('Y-m-d H:i:s');
        $this->assertLessThanOrEqual(
            $now,
            $this->localeDate->convertConfigTimeToUtc($product->getSpecialFromDate())
        );
        $this->assertNull($product->getSpecialToDate());
        $this->assertLessThanOrEqual(
            $now,
            $this->localeDate->convertConfigTimeToUtc($product->getNewsFromDate())
        );
        $this->assertNull($product->getNewsToDate());
    }

    #[
        DataFixture(UpdateFixture::class, as: 'update1'),
        DataFixture(ProductFixture::class, as: 'product1'),
    ]
    public function testFromToDatetimeForScheduledUpdate(): void
    {
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);
        $product = $this->fixtures->get('product1');
        $update = $this->fixtures->get('update1');
        $sku = $product->getSku();
        $product = $this->productRepository->get($sku, storeId: Store::DEFAULT_STORE_ID, forceReload: true);
        $product->setSpecialPrice(9);
        $product->setIsNew(1);
        $this->versionManager->setCurrentVersionId($update->getId());
        $this->productStaging->schedule($product, $update->getId());
        $product = $this->productRepository->get($sku, storeId: Store::DEFAULT_STORE_ID, forceReload: true);
        $this->assertEquals(
            $update->getStartTime(),
            $this->localeDate->convertConfigTimeToUtc($product->getSpecialFromDate())
        );
        $this->assertEquals(
            $update->getEndTime(),
            $this->localeDate->convertConfigTimeToUtc($product->getSpecialToDate())
        );
        $this->assertEquals(
            $update->getStartTime(),
            $this->localeDate->convertConfigTimeToUtc($product->getNewsFromDate())
        );
        $this->assertEquals(
            $update->getEndTime(),
            $this->localeDate->convertConfigTimeToUtc($product->getNewsToDate())
        );
    }
}
