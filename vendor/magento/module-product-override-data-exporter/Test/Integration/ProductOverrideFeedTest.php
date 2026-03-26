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

namespace Magento\ProductOverrideDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection;
use Magento\TestFramework\Catalog\Model\GetCategoryByName;
use Magento\CatalogPermissions\Model\Permission as CatalogPermissions;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Store\Model\Store as DefaultStore;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;

/**
 * Test the product override data feed
 * TODO: Cover different product types
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[
    DbIsolation(false)
]
class ProductOverrideFeedTest extends ProductOverrideTestAbstract
{
    /**
     * @var string
     */
    private const EXPECTED_DATE_TIME_FORMAT = '%d-%d-%d %d:%d:%d';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    private ?DataFixtureStorage $fixtures;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        Config('catalog/magento_catalogpermissions/enabled', 0, 'store', 'default'),
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'category'),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$category.id$'],
            ],
            'product'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$category.id$',
                'website_id' => DefaultStore::DISTRO_STORE_ID,
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_catalog_product_price' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_checkout_items' => CatalogPermissions::PERMISSION_ALLOW,
            ]
        ),
    ]
    public function testWithCategoryPermissionsDisabled()
    {
        $product = $this->fixtures->get('product');
        $this->runIndexer();
        $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
        self::assertEmpty($productsFeed, "Product feed should be empty if category permissions are turned off");
    }

    #[
        Config('catalog/magento_catalogpermissions/enabled', 1, 'store', 'default'),
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'category'),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$category.id$'],
            ],
            'product'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$category.id$',
                'website_id' => DefaultStore::DISTRO_STORE_ID,
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_catalog_product_price' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_checkout_items' => CatalogPermissions::PERMISSION_ALLOW,
            ]
        ),
    ]
    public function testProductsWithPermissions()
    {
        $product = $this->fixtures->get('product');
        $this->runIndexer(true);
        $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
        self::assertNotEmpty($productsFeed);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productsFeed as $feed) {
            $this->checkModifiedAtField($feed, $modifiedAt);
            self::assertEquals("base", $feed['websiteCode']);
            self::assertEquals("simple-product-in-allowed-category", $feed['sku']);
            self::assertFalse($feed['deleted']);
            self::assertTrue($feed['displayable']);
            self::assertTrue($feed['priceDisplayable']);
            self::assertTrue($feed['addToCartAllowed']);
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_same_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/product_with_permissions_for_two_stores.php
     */
    public function testProductsWithPermissionsAndMultipleStores()
    {
        $product = $this->productRepository->get('12345-1');
        $this->runIndexer(true);
        $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
        self::assertNotEmpty($productsFeed);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productsFeed as $feed) {
            $this->checkModifiedAtField($feed, $modifiedAt);
            self::assertEquals("base", $feed['websiteCode']);
            self::assertEquals("12345-1", $feed['sku']);
            self::assertFalse($feed['deleted']);
            self::assertTrue($feed['displayable']);
            self::assertTrue($feed['priceDisplayable']);
            self::assertTrue($feed['addToCartAllowed']);
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_same_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/two_products_with_permissions_for_two_stores.php
     */
    public function testTwoProductsWithPermissionsAndMultipleStores()
    {
        $productSkus = ['12345-1', '12345-2'];
        $this->runIndexer(true);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productSkus as $sku) {
            $product = $this->productRepository->get($sku);
            $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
            self::assertNotEmpty($productsFeed);
            foreach ($productsFeed as $feed) {
                $this->checkModifiedAtField($feed, $modifiedAt);
                self::assertEquals("base", $feed['websiteCode']);
                self::assertEquals($sku, $feed['sku']);
                self::assertEquals(false, $feed['deleted']);
                self::assertEquals(true, $feed['displayable']);
                self::assertEquals(true, $feed['priceDisplayable']);
                self::assertEquals(true, $feed['addToCartAllowed']);
            }
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_second_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/two_products_with_permissions_for_two_stores_on_different_website.php
     */
    public function testTwoProductsWithPermissionsAndMultipleWebsites()
    {
        $productSkus = ['12345-1' => 'base', '12345-2' => 'test'];
        $this->runIndexer(true);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productSkus as $sku => $website) {
            $product = $this->productRepository->get($sku);
            $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
            self::assertNotEmpty($productsFeed);
            foreach ($productsFeed as $feed) {
                $this->checkModifiedAtField($feed, $modifiedAt);
                self::assertEquals($website, $feed['websiteCode']);
                self::assertEquals($sku, $feed['sku']);
                self::assertEquals(false, $feed['deleted']);
                self::assertEquals(true, $feed['displayable']);
                self::assertEquals(true, $feed['priceDisplayable']);
                self::assertEquals(true, $feed['addToCartAllowed']);
            }
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_same_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/product_with_permissions_for_two_stores.php
     */
    public function testProductsWithDeletedPermissionsAndMultipleStores()
    {
        $product = $this->productRepository->get('12345-1');

        $getCategoryByName = $this->objectManager->create(GetCategoryByName::class);
        $secondRootCategoryId = $getCategoryByName->execute("Second Root Category")->getId();

        $permission =
            $this->objectManager->create(Collection::class)
                ->addFieldToFilter('category_id', $secondRootCategoryId)
                ->getFirstItem();

        $permission->delete();

        $this->runIndexer(true);
        $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
        self::assertNotEmpty($productsFeed);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productsFeed as $feed) {
            $this->checkModifiedAtField($feed, $modifiedAt);
            self::assertEquals("base", $feed['websiteCode']);
            self::assertEquals("12345-1", $feed['sku']);
            self::assertEquals(false, $feed['deleted']);
            self::assertEquals(true, $feed['displayable']);
            self::assertEquals(true, $feed['priceDisplayable']);
            self::assertEquals(true, $feed['addToCartAllowed']);
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_same_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/two_products_with_permissions_for_two_stores.php
     */
    public function testTwoProductsWithDeletedPermissionsAndMultipleStores()
    {
        $getCategoryByName = $this->objectManager->create(GetCategoryByName::class);
        $secondRootCategoryId = $getCategoryByName->execute("Second Root Category")->getId();

        $permission =
            $this->objectManager->create(Collection::class)
                ->addFieldToFilter('category_id', $secondRootCategoryId)
                ->getFirstItem();

        $permission->delete();

        $this->runIndexer(true);

        $productSkus = ['12345-1', '12345-2'];
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productSkus as $sku) {
            $product = $this->productRepository->get($sku);
            $productsFeed = $this->getProductOverrideFeedByIds([$product->getId()]);
            self::assertNotEmpty($productsFeed);
            foreach ($productsFeed as $feed) {
                $this->checkModifiedAtField($feed, $modifiedAt);
                self::assertEquals("base", $feed['websiteCode']);
                self::assertEquals($sku, $feed['sku']);
                self::assertEquals(false, $feed['deleted']);
                self::assertEquals(true, $feed['displayable']);
                self::assertEquals(true, $feed['priceDisplayable']);
                self::assertEquals(true, $feed['addToCartAllowed']);
            }
        }
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/store_with_second_root_category_on_second_website.php
     * @magentoDataFixture Magento_ProductOverrideDataExporter::Test/_files/two_products_with_permissions_for_two_stores_on_different_website.php
     */
    public function testTwoProductsWithDeletedPermissionsAndMultipleWebsites()
    {
        $getCategoryByName = $this->objectManager->create(GetCategoryByName::class);
        $secondRootCategoryId = $getCategoryByName->execute("Second Root Category")->getId();

        $permission =
            $this->objectManager->create(Collection::class)
                ->addFieldToFilter('category_id', $secondRootCategoryId)
                ->getFirstItem();

        $permission->delete();

        $this->runIndexer(true);

        $productSkus = ['12345-1', '12345-2'];
        $productsId = [];
        foreach ($productSkus as $sku) {
            $productsId[] = $this->productRepository->get($sku)->getId();
        }
        $productsFeed = $this->getProductOverrideFeedByIds($productsId);
        self::assertCount(1, $productsFeed);
        $modifiedAt = (new \DateTime())->getTimestamp();
        foreach ($productsFeed as $feed) {
            $this->checkModifiedAtField($feed, $modifiedAt);
            self::assertEquals("base", $feed['websiteCode']);
            self::assertEquals('12345-1', $feed['sku']);
            self::assertEquals(false, $feed['deleted']);
            self::assertEquals(true, $feed['displayable']);
            self::assertEquals(true, $feed['priceDisplayable']);
            self::assertEquals(true, $feed['addToCartAllowed']);
        }
    }

    #[
        AppArea('adminhtml'),
        Config('catalog/magento_catalogpermissions/enabled', 0, 'store', 'default'),
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'category'),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$category.id$'],
            ],
            'product'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$category.id$',
                'website_id' => DefaultStore::DISTRO_STORE_ID,
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_catalog_product_price' => CatalogPermissions::PERMISSION_ALLOW,
                'grant_checkout_items' => CatalogPermissions::PERMISSION_ALLOW,
            ]
        ),
    ]
    public function testDeletedProductsWithPermissions()
    {
        $productData = $this->fixtures->get('product');
        $product = $this->productRepository->get($productData->getSku());
        if ($product->getId()) {
            $this->productRepository->delete($product);
        }
        $this->runIndexer(false, true);
        $productFeed = $this->getProductOverrideFeedByIds([$product->getId()]);

        self::assertEmpty($productFeed);
    }

    /**
     * @param mixed $feed
     * @param int $modifiedAt
     * @return void
     * @throws \Exception
     */
    private function checkModifiedAtField(array $feed, int $modifiedAt): void
    {
        $this->assertNotEmpty($feed['modifiedAt']);
        $this->assertStringMatchesFormat(self::EXPECTED_DATE_TIME_FORMAT, $feed['modifiedAt']);
        $actualModifiedAt = (new \DateTime($feed['modifiedAt']))->getTimestamp();
        $this->assertEqualsWithDelta($modifiedAt, $actualModifiedAt, 3);
    }
}
