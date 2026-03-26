<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\SaaSCatalog\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SaaSCatalog\Cron\ProductSubmitFeed;
use Magento\SaaSCommon\Cron\SubmitFeedInterface;
use Magento\SaaSCommon\Model\Http\Command\SubmitFeed as SubmitFeedCommand;
use Magento\SaaSCommon\Test\Integration\SubmitFeedStub;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestModuleDataSentOutside\Model\AccessedData;

/**
 * Test class to check products feed submit functionality
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class SubmitProductsFeedTest extends AbstractProductTestHelper
{
    /**
     * @var SubmitFeedInterface
     */
    private $submitFeed;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Bootstrap::getObjectManager()->configure([
            'preferences' => [
                SubmitFeedCommand::class =>
                    SubmitFeedStub::class,
            ]
        ]);
        $connection = Bootstrap::getObjectManager()->create(ResourceConnection::class)->getConnection();
        $feedTable = $connection->getTableName(
            Bootstrap::getObjectManager()->get(FeedPool::class)
                ->getFeed('products')
                ->getFeedMetadata()
                ->getFeedTableName()
        );
        $connection->truncateTable($feedTable);
    }

    /**
     * Integration test setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->submitFeed = Bootstrap::getObjectManager()->get(ProductSubmitFeed::class); // @phpstan-ignore-line
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture magento_saas/environment production
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_with_media_gallery.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testLogDataSentOutside() : void
    {
        $sku = 'simple1';
        $product = $this->productRepository->get($sku);
        $this->emulatePartialReindexBehavior([$product->getId()]);

        $this->submitFeed->execute();
        $storeViewCodes = ['default', 'fixture_second_store'];
        foreach ($storeViewCodes as $storeViewCode) {
            $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
            self::assertNotEmpty($extractedProduct);
        }
        $accessedData = Bootstrap::getObjectManager()->get(AccessedData::class);
        self::assertEmpty($accessedData->getData());
    }
}
