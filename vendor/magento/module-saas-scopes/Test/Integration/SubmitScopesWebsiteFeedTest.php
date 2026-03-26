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

namespace Magento\SaaSScopes\Test\Integration;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Indexer\Model\Indexer;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Registry;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\ScopesDataExporter\Model\Indexer\ScopesWebsiteFeedIndexMetadata;

/**
 * Test class to check scopes website submit feed functionality
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubmitScopesWebsiteFeedTest extends TestCase
{
    /**
     * Order feed indexer
     */
    private const SCOPES_WEBSITE_FEED_INDEXER = 'scopes_website_data_exporter';

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var BatchGeneratorInterface
     */
    private $batchGenerator;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $connection = Bootstrap::getObjectManager()->create(ResourceConnection::class)->getConnection();
        $feedTable = $connection->getTableName(
            Bootstrap::getObjectManager()->get(FeedPool::class)
                ->getFeed('scopesWebsite')
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
        $objectManager = Bootstrap::getObjectManager();
        $this->indexer = $objectManager->create(Indexer::class);
        $this->batchGenerator = $objectManager->create(\Magento\DataExporter\Model\Batch\Feed\Generator::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture magento_saas/environment production
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento/Store/_files/second_website_with_store_group_and_store.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \Throwable
     */
    public function testScopesWebsite() : void
    {

        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = Bootstrap::getObjectManager()->create(WebsiteRepositoryInterface::class);
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = Bootstrap::getObjectManager()->create(StoreRepositoryInterface::class);

        $website = $websiteRepository->get('test');
        $websiteId = $website->getId();
        $storeId = $storeRepository->get('fixture_second_store')->getStoreGroupId();
        $storeViewId = $storeRepository->get('fixture_second_store')->getId();
        $this->runIndexer([$websiteId]);

        $expectedFeed = [
            'websiteId' => $websiteId,
            'websiteCode' => 'test',
            'stores' => [
                [
                    'storeId' => $storeId,
                    'storeCode' => 'second_group',
                    'storeViews' => [
                        [
                            'storeViewId' => $storeViewId,
                            'storeViewCode' => 'fixture_second_store',
                        ]
                    ],
                ]
            ],
        ];
        $expectedFeedBeforeDelete = $expectedFeed;
        $expectedFeedBeforeDelete['deleted'] = false;
        $expectedFeedAfterDelete = $expectedFeed;
        $expectedFeedAfterDelete['deleted'] = true;

        $lastSyncTimestamp = '1';
        $metadata = Bootstrap::getObjectManager()->get(ScopesWebsiteFeedIndexMetadata::class); // @phpstan-ignore-line
        $batchIterator = $this->batchGenerator->generate($metadata, ['sinceTimestamp' => $lastSyncTimestamp]);
        $actualBatch = $batchIterator->current();
        self::assertCount(1, $actualBatch['feed']);
        self::assertEquals($expectedFeedBeforeDelete, $actualBatch['feed'][0]);
        // Delete website
        $this->deleteWebsite($website);
        $this->runIndexer([$websiteId]);
        $batchIterator = $this->batchGenerator->generate($metadata, ['sinceTimestamp' => $lastSyncTimestamp]);
        $actualBatch = $batchIterator->current();
        self::assertCount(1, $actualBatch['feed']);
        self::assertEquals($expectedFeedAfterDelete, $actualBatch['feed'][0]);
    }

    /**
     * Run the indexer to extract scopesData data
     *
     * @param array $ids
     * @return void
     */
    private function runIndexer(array $ids) : void
    {
        try {
            $this->indexer->load(self::SCOPES_WEBSITE_FEED_INDEXER);
            $this->indexer->reindexList($ids);
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @param WebsiteInterface $website
     * @return void
     * @throws \Exception
     */
    private function deleteWebsite(WebsiteInterface $website) : void
    {
        /** @var Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(Registry::class);

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        /** @var WebsiteResource $websiteResource */
        $websiteResource = Bootstrap::getObjectManager()->get(WebsiteResource::class);
        $websiteResource->load($website, 'test', 'code');
        $websiteResource->delete($website);

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
    }
}
