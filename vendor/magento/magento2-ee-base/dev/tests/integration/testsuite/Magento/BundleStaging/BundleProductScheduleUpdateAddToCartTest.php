<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\BundleStaging;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Test\Fixture\Update as UpdateFixture;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test saving Bundle product with a Scheduled Update
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BundleProductScheduleUpdateAddToCartTest extends TestCase
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $repository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductStaging
     */
    private $productStaging;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @var int
     */
    private $currentStoreId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->create(UpdateRepositoryInterface::class);
        $this->resourceConnection = $objectManager->get(ResourceConnection::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->productStaging = Bootstrap::getObjectManager()->create(ProductStagingInterface::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->versionManager = Bootstrap::getObjectManager()->get(VersionManager::class);
        $this->currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->currentStoreId = $this->storeManager->getStore()->getId();
    }

    protected function tearDown(): void
    {
        $this->versionManager->setCurrentVersionId($this->currentVersionId);
        $this->storeManager->setCurrentStore($this->currentStoreId);
    }

    #[
        DbIsolation(true),
        DataFixture(UpdateFixture::class, as: 'update1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple1', 'price' => 10], as:'p1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple2', 'price' => 20], as:'p2'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$p1.sku$', 'price' => 10, 'price_type' => 0], as:'link1'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$p2.sku$', 'price' => 25, 'price_type' => 1], as:'link2'),
        DataFixture(BundleOptionFixture::class, ['title' => 'Checkbox Options', 'type' => 'checkbox',
            'required' => 1,'product_links' => ['$link1$', '$link2$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['title' => 'Multiselect Options', 'type' => 'multi',
            'required' => 1,'product_links' => ['$link1$', '$link2$']], 'opt2'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle-product-multiselect-checkbox-options','price' => 50,'price_type' => 1,
                '_options' => ['$opt1$', '$opt2$']],
            as:'bp1'
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(
            AddBundleProductToCart::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$bp1.id$',
                'selections' => [['$p1.id$'], ['$p1.id$', '$p2.id$']],
                'qty' => 1
            ]
        )
    ]
    public function testScheduleUpdateForBundleProductAddToCart()
    {
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);
        $bundleProduct = $this->fixtures->get('bp1');
        $cart = $this->fixtures->get('cart');
        $quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $quote = $quoteRepository->getActive($cart->getId());
        $this->assertCount(1, $quote->getItems());

        $bundleProduct->setData('description', 'test');
        $this->productRepository->save($bundleProduct);

        $update = $this->fixtures->get('update1');
        $this->versionManager->setCurrentVersionId($update->getId());
        $this->productStaging->schedule($bundleProduct, $update->getId());
        $this->versionManager->setCurrentVersionId($this->currentVersionId);

        $updatedQuote = $quoteRepository->getActive($cart->getId());
        $this->assertCount(1, $updatedQuote->getItems());
    }
}
