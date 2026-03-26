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

namespace Magento\SaaSCatalog\Test\Api;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Exception\LocalizedException;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\Store\Model\Store;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestModuleSaasConnector\Model\ServicesConfig as ServicesConfigTest;
use Magento\UrlRewrite\Test\Fixture\UrlRewrite as UrlRewriteFixture;
use PHPUnit\Framework\Constraint\Callback;

/**
 * Tests for products creation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateSimpleProductTest extends AbstractSaasCatalogTestHelper
{
    /**
     * Test create simple product
     *
     * @param array $expected
     * @dataProvider expectedDataProvider
     * @throws LocalizedException
     */
    #[
        DbIsolation(false),
        ConfigFixture(ServicesConfigTest::CONFIG_PATH_SERVICES_CONNECTOR_ENVIRONMENT, 'production'),
        ConfigFixture(ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY, 'test_key'),
        ConfigFixture(ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY, 'private_test_key'),
        ConfigFixture(ServicesConfig::CONFIG_PATH_ENVIRONMENT_ID, 'test_env_id'),
        ConfigFixture(Store::XML_PATH_UNSECURE_BASE_URL, 'http://magento.com/'),
        DataFixture(ProductFixture::class, as:'simpleProduct'),
        DataFixture(UrlRewriteFixture::class, as:'url')
    ]
    public function testCreateProduct(array $expected): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $productData = $fixtures->get('simpleProduct')->getData();
        $expected['productId'] = $productData['entity_id'];
        $expected['name'] = $productData['name'];
        $expected['urlKey'] = $productData['url_key'];
        $expected['sku'] = $productData['sku'];
        $expected['url'] .= $productData['url_key'] . '.html';
        $expected['urlRewrites'][0]['url'] .= $productData['url_key'] . '.html';

        $this->triggerSyncWithExpectation();
        $this->assertArrayMatchesExpected($expected, $this->getResponse(0));
    }

    /**
     * @phpcs:disable Generic.Files.LineLength
     */
    public static function expectedDataProvider(): array
    {
        return [
            [
                [
                    'sku' => 'simple',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'name' => self::isType('string'),
                    'productId' => self::isType('int'),
                    'type' => 'simple',
                    'image' =>
                        [
                            'url' => 'http://magento.com/media/catalog/productno_selection',
                            'label' => null,
                        ],
                    'smallImage' =>
                        [
                            'url' => 'http://magento.com/media/catalog/productno_selection',
                            'label' => null,
                        ],
                    'status' => 'Enabled',
                    'swatchImage' =>
                        [
                            'url' => 'http://magento.com/media/catalog/productno_selection',
                            'label' => null,
                        ],
                    'taxClassId' => 'Taxable Goods',
                    'thumbnail' =>
                        [
                            'url' => 'http://magento.com/media/catalog/productno_selection',
                            'label' => null,
                        ],
                    'createdAt' => new Callback(fn($date) => (new \DateTime($date)) <= (new \DateTime())),
                    'updatedAt' => new Callback(fn($date) => (new \DateTime($date)) <= (new \DateTime())),
                    'urlKey' => 'simple',
                    'visibility' => 'Catalog, Search',
                    'weight' => 1,
                    'weightUnit' => 'lbs',
                    'currency' => 'USD',
                    'displayable' => true,
                    'buyable' => true,
                    'attributes' => [
                        [
                            'attributeCode' => 'ac_attribute_set',
                            'value' => ['Default'],
                        ],
                        [
                            'attributeCode' => 'ac_tax_class',
                            'value' => ['Taxable Goods'],
                        ],
                        [
                            'attributeCode' => 'ac_inventory',
                            'value' => [
                                '{"manageStock":true,"cartMinQty":1,"cartMaxQty":10000,"backorders":"no","enableQtyIncrements":false,"qtyIncrements":1}'
                            ],
                        ],
                    ],
                    'categoryData' => null,
                    'media_gallery' => null,
                    'optionsV2' => null,
                    'shopperInputOptions' => null,
                    'samples' => null,
                    'images' => null,
                    'videos' => null,
                    'links' => null,
                    'inStock' => true,
                    'lowStock' => false,
                    'variants' => null,
                    'parents' => null,
                    'url' => 'http://magento.com/index.php/',
                    'urlRewrites' =>
                        [
                            0 =>
                                [
                                    'url' => 'http://magento.com/',
                                    'parameters' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'id',
                                                    'value' => self::isType('string'),
                                                ],
                                        ],
                                ],
                        ],
                    'deleted' => false,
                    'modifiedAt' => new Callback(fn($date) => (new \DateTime($date)) <= (new \DateTime())),
                ]
            ]
        ];
    }
}
