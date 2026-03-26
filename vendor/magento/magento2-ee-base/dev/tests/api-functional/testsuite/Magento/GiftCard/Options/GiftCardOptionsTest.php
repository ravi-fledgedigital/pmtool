<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Options;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\GiftCard\Test\Fixture\PhysicalGiftCard as GiftCardProductFixture;
use Magento\GiftCard\Test\Fixture\GiftCard as GiftCardVirtualProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\CatalogPermissions\Model\Permission;

/**
 * Test for giftcard options
 */
class GiftCardOptionsTest extends GraphQlAbstract
{

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }
    /**
     * Given Catalog Permissions are enabled
     * And 1 categories "Allowed Category" created
     * And "Allowed Category" grants all permissions on logged in customer group
     * And a giftcard product is assigned to "Allowed Category"
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 0, //NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        )
    ]
    public function testQueryForPhysicalGiftCardOptions(): void
    {
        $this->reindexCatalogPermissions();
        /** @var ProductInterface $giftCardProductInAllowedCategory */
        $giftCardProductInAllowedCategory = $this->fixtures->get('giftcard_product_in_allowed_category');
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);
        $productResponse = $this->graphQlQuery(
            $this->getQuery($giftCardProductInAllowedCategory->getSku()),
            [],
            '',
            $headerAuthorization
        );
        $responseProduct = $productResponse['products']['items'][0];
            self::assertNotEmpty($responseProduct['gift_card_options']);
            self::assertNotEmpty($responseProduct['gift_card_options'][0]['title']);
            self::assertNotEmpty($responseProduct['gift_card_options'][1]['title']);
            self::assertEquals('Sender Name', $responseProduct['gift_card_options'][0]['title']);
            self::assertEquals('Recipient Name', $responseProduct['gift_card_options'][1]['title']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And 1 categories "Allowed Category" created
     * And "Allowed Category" grants all permissions on logged in customer group
     * And a giftcard product is assigned to "Allowed Category"
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            GiftCardVirtualProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 0, //NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        )
    ]
    public function testQueryForVirtualGiftCardOptions(): void
    {
        $this->reindexCatalogPermissions();
        /** @var ProductInterface $giftCardProductInAllowedCategory */
        $giftCardProductInAllowedCategory = $this->fixtures->get('giftcard_product_in_allowed_category');
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);
        $productResponse = $this->graphQlQuery(
            $this->getQuery($giftCardProductInAllowedCategory->getSku()),
            [],
            '',
            $headerAuthorization
        );
        $responseProduct = $productResponse['products']['items'][0];
        self::assertNotEmpty($responseProduct['gift_card_options']);
        self::assertNotEmpty($responseProduct['gift_card_options'][0]['title']);
        self::assertNotEmpty($responseProduct['gift_card_options'][1]['title']);
        self::assertNotEmpty($responseProduct['gift_card_options'][2]['title']);
        self::assertNotEmpty($responseProduct['gift_card_options'][3]['title']);
        self::assertEquals('Sender Name', $responseProduct['gift_card_options'][0]['title']);
        self::assertEquals('Recipient Name', $responseProduct['gift_card_options'][1]['title']);
        self::assertEquals('Sender Email', $responseProduct['gift_card_options'][2]['title']);
        self::assertEquals('Recipient Email', $responseProduct['gift_card_options'][3]['title']);
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());

        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category");
    }

    /**
     * Get query
     *
     * @param string $sku
     *
     * @return string
     */
    private function getQuery(string $sku): string
    {
        return <<<QUERY
query {
  products(filter: { sku: { eq: "$sku" } }) {
        total_count
        items {
            uid
            name
            sku
            __typename

            url_key
            ... on GiftCardProduct {
                giftcard_type
                options {
                    uid
                    title
                    required
                }
                gift_card_options {
                    title
                    ... on CustomizableFieldOption {
                        value {
                            uid
                        }
                        required
                    }
                }

                giftcard_amounts {
                    uid
                    value
                }
            }
        }
    }
}
QUERY;
    }
}
