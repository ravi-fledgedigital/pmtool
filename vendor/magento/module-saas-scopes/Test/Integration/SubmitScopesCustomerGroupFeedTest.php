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

use Magento\Customer\Model\Group;
use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\Feed\Generator;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\ScopesDataExporter\Model\Indexer\ScopesCustomerGroupFeedIndexMetadata;

/**
 * Test class to check scopes customer group submit feed functionality
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubmitScopesCustomerGroupFeedTest extends TestCase
{
    /**
     * Scopes customer group feed indexer
     */
    private const SCOPES_WEBSITE_FEED_INDEXER = 'scopes_customergroup_data_exporter';

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
                ->getFeed('scopesCustomerGroup')
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
        $this->batchGenerator = $objectManager->create(Generator::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture magento_saas/environment production
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento/Customer/_files/customer_group.php
     *
     * @throws \Throwable
     */
    public function testScopesWebsite() : void
    {
        /** @var Group $customerGroup */
        $customerGroup = Bootstrap::getObjectManager()->create(Group::class)
            ->load('custom_group', 'customer_group_code');
        $customerGroupId = $customerGroup->getId();
        $this->runIndexer([$customerGroupId]);

        $expectedFeed = [
            'customerGroupId' => $customerGroupId,
            'customerGroupCode' => sha1((string) $customerGroupId),
            'websites' => ['base'],
        ];
        $expectedFeedBeforeDelete = $expectedFeed;
        $expectedFeedBeforeDelete['deleted'] = false;
        $expectedFeedAfterDelete = $expectedFeed;
        $expectedFeedAfterDelete['deleted'] = true;

        $lastSyncTimestamp = "1";
        $metadata = Bootstrap::getObjectManager()
            ->get(ScopesCustomerGroupFeedIndexMetadata::class); // @phpstan-ignore-line
        $batchIterator = $this->batchGenerator->generate($metadata, ['sinceTimestamp' => $lastSyncTimestamp]);
        $actualBatch = $batchIterator->current();
        self::assertCount(1, $actualBatch['feed']);
        self::assertEquals($expectedFeedBeforeDelete, $actualBatch['feed'][0]);
        // Delete customer group
        $this->deleteCustomerGroup($customerGroupId);
        $this->runIndexer([$customerGroupId]);
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
     * @param string $customerGroupId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    private function deleteCustomerGroup(string $customerGroupId) : void
    {
        $customerGroupRepository = Bootstrap::getObjectManager()->get(GroupRepositoryInterface::class);
        $customerGroup = $customerGroupRepository->getById($customerGroupId);
        $customerGroupRepository->delete($customerGroup);
    }
}
