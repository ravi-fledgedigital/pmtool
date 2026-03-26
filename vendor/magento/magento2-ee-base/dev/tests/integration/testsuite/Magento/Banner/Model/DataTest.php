<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Model;

use Magento\Banner\Model\Banner\Data;
use Magento\Banner\Model\Banner\DataFactory;
use Magento\Banner\Test\Fixture\Banner as BannerFixture;
use Magento\Catalog\Test\Fixture\Product;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogRule\Test\Fixture\Rule as CatalogRuleFixture;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\SalesRule\Test\Fixture\AddressCondition;
use Magento\SalesRule\Test\Fixture\Rule as RuleFixture;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Test\Fixture\Store;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    private $bannersData;

    /**
     * @var DataFactory
     */
    private $bannersDataFactory;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $this->bannersDataFactory = Bootstrap::getObjectManager()->get(
            DataFactory::class
        );
        $this->bannersData = Bootstrap::getObjectManager()->create(
            Data::class
        );
        $this->storeManager = Bootstrap::getObjectManager()->get(
            StoreManagerInterface::class
        );
        $this->checkoutSession = Bootstrap::getObjectManager()->get(
            Session::class
        );
    }

    /**
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @magentoDbIsolation disabled
     * @magentoAppArea frontend
     */
    public function testGetSectionData()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $objectManager->get(\Magento\Catalog\Model\Product::class)->loadByAttribute('sku', 'simple');
        $product->load($product->getId());
        $objectManager->get(\Magento\CatalogRule\Model\Indexer\IndexBuilder::class)
            ->reindexById((int)$product->getId());
        $banner = $objectManager->create(\Magento\Banner\Model\Banner::class);
        $banner->load('Test Dynamic Block', 'name');
        $data = $this->bannersData->getSectionData();
        $this->assertNotEmpty($data['items']['fixed']);
        $this->assertArrayHasKey($banner->getId(), $data['items']['fixed']);
    }

    #[
        AppArea('frontend'),
        DbIsolation(false),
        DataFixture(Store::class, [], as: 's1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple'], 'p1'),
        DataFixture(CatalogRuleFixture::class, [], as: 'cr1'),
        DataFixture(
            AddressCondition::class,
            ['attribute' => 'base_subtotal', 'operator' => '>=', 'value' => 0],
            as: 'ac1'
        ),
        DataFixture(
            RuleFixture::class,
            ['stop_rules_processing' => 0, 'simple_free_shipping' => 1, 'conditions' => ['$ac1$']],
            as: 'sr1'
        ),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner1'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner2'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner3'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner4'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner5'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner6'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner7'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner8'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner9'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$cr1.id$']], as: 'banner10'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner11'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner12'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner13'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner14'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner15'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner16'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner17'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner18'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner19'),
        DataFixture(BannerFixture::class, ['banner_sales_rules' => ['$sr1.id$']], as: 'banner20'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => 0,
                'content' => 'Default Store Banner Content%uniqid%'
            ],
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner21'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner22'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner23'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner24'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner25'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner26'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner27'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner28'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner29'),
        DataFixture(BannerFixture::class, ['store_contents' => [
            [
                'store_id' => '$s1.id$',
                'content' => 'Custom Store Banner Content%uniqid%'
            ]
        ]], as: 'banner30'),
        DataFixture(BannerFixture::class, [], as: 'banner31'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testGetSectionDataWithMultipleBanners(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $store = $fixtures->get('s1');
        $cart = $fixtures->get('cart');
        $product = $fixtures->get('p1');
        $customStoreContentBanner = $fixtures->get('banner21');

        $this->checkoutSession->setQuoteId($cart->getId());
        $this->bannersData = $this->bannersDataFactory->create(
            ['data' => ['product_id' => $product->getId()]]
        );
        $data = $this->bannersData->getSectionData();

        $this->assertCount(22, $data['items']['fixed']);
        $this->assertCount(10, $data['items']['salesrule']);
        $this->assertCount(10, $data['items']['catalogrule']);
        $this->assertStringContainsString(
            'Default Store Banner Content',
            $data['items']['fixed'][$customStoreContentBanner->getId()]['content']
        );

        $this->storeManager->setCurrentStore($store->getId());
        $this->bannersData = $this->bannersDataFactory->create(
            ['data' => ['product_id' => $product->getId()]]
        );
        $data = $this->bannersData->getSectionData();

        $this->assertCount(31, $data['items']['fixed']);
        $this->assertCount(10, $data['items']['salesrule']);
        $this->assertCount(10, $data['items']['catalogrule']);
        $this->assertNotEmpty($data['items']['fixed'][$customStoreContentBanner->getId()]);
        $this->assertStringContainsString(
            'Custom Store Banner Content',
            $data['items']['fixed'][$customStoreContentBanner->getId()]['content']
        );
    }
}
