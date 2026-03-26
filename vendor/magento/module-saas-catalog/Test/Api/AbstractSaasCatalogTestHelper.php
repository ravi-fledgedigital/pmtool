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

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Exception\LocalizedException;
use Magento\SaaSCommon\Model\Http\Converter\GzipConverter;
use Magento\Indexer\Cron\UpdateMview;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\TestModuleSaasConnector\Model\ClientResolver;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Abstract class to keep logic related to sending and verification MDEE data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractSaasCatalogTestHelper extends WebapiAbstract
{
    private ClientResolver $client;
    private GzipConverter $converter;
    private UpdateMview $mViewCron;

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->client = $objectManager->create(ClientResolver::class, ['test' => 'RestTest']);
        $this->converter = $objectManager->create(GzipConverter::class);
        $objectManager->addSharedInstance($this->client, ClientResolver::class);
        $this->mViewCron = $objectManager->create(UpdateMview::class);
        Bootstrap::getObjectManager()->configure([
            'Magento\CatalogDataExporter\Model\Indexer\ProductFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ],
            'Magento\CatalogDataExporter\Model\Indexer\CategoryFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ],
            'Magento\CatalogDataExporter\Model\Indexer\ProductAttributeFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ]
        ]);
        parent::setUp();
    }

    public function triggerSyncWithExpectation(int $statusCode = 200, ?string $response = null): void
    {
        $this->client->addResponse(new Response(
            $statusCode,
            ['x-request-id' => 'd4885086e48053e8'],
            $response ?? '{"feedItemReceivedCount": 1,"invalidFeedItems": []}'
        ));
        $this->mViewCron->execute();
    }

    protected function assertArrayMatchesExpected(array $expected, array $actual): void
    {
        foreach ($expected as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $actual, "Field '$key' is missing in the provided array.");
            if (is_array($expectedValue)) {
                $this->assertArrayMatchesExpected($expectedValue, $actual[$key]);
                continue;
            }

            if ($expectedValue instanceof Constraint) {
                $this->assertThat($actual[$key], $expectedValue, "Field '$key' did not pass the condition check.");
            } else {
                $this->assertEquals($expectedValue, $actual[$key], "Field '$key' does not match the expected value.");
            }
        }
    }

    protected function getResponse(int $id): array
    {
        $history = $this->client->getHistory();
        if (empty($history)) {
            self::fail('No requests were sent to the server.');
        }
        $body = $this->client->getHistory()[$id]['request']->getBody();
        return $this->converter->fromBody(\gzdecode($body->getContents()))[0];
    }
}
