<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportCsv\Api;

use Magento\AsynchronousOperations\Api\BulkStatusInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PreconditionFailedException;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Translation\Test\Fixture\Translation;

#[
    DataFixture(
        Translation::class,
        [
            'string' => 'Catalog, Search',
            'translate' => 'Katalog, Suche',
            'locale' => 'de_DE',
        ]
    ),
    DataFixture(
        Translation::class,
        [
            'string' => 'Block after Info Column',
            'translate' => 'Block nach Info-Spalte',
            'locale' => 'de_DE',
        ]
    ),
    DataFixture(
        Translation::class,
        [
            'string' => 'Use config',
            'translate' => 'Konfiguration verwenden',
            'locale' => 'de_DE',
        ]
    )
]
class ImportCsvApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/csv';
    private const SERVICE_NAME = 'importCsvApiImportLocalizedSourceDataV1';
    private const SERVICE_VERSION = 'V1';
    private const ASYNC_PATH = '/async/bulk';
    private const BULK_UUID_KEY = 'bulk_uuid';

    /**
     * @var PublisherConsumerController
     */
    private $publisherConsumerController;

    /**
     * Test Rest API Import
     *
     * @param array $requestData
     * @param array $expectedResponse
     * @dataProvider getRequestData
     */
    public function testImport(array $requestData, array $expectedResponse): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Execute'
            ]
        ];
        $requestData['source']['csvData'] = base64_encode(file_get_contents($requestData['source']['csvData']));
        $response = $this->_webApiCall($serviceInfo, $requestData);
        $this->assertEquals($expectedResponse, array_values($response));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRequestData(): array
    {
        return [
            [
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/products.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 3'
                ]
            ],
            [
                'requestData' => [
                    'source' => [
                        'entity' => 'advanced_pricing',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/advanced_pricing.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ],
            [  // Confirming that ImportFieldSeparator is loaded
                'requestData' => [
                    'source' => [
                        'ImportFieldSeparator' => ';',
                        'entity' => 'advanced_pricing',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/advanced_pricing_semicolon.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ],
            [  // Verifying that incorrect ImportFieldSeparator will error
                'requestData' => [
                    'source' => [
                        'ImportFieldSeparator' => ';',
                        'entity' => 'advanced_pricing',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/advanced_pricing.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 1: We can't find required columns: sku.",
                    1 => 'Row 1: Column names: "sku,tier_price_website,tier_price_customer_group,'
                        . 'tier_price_qty,tier_price,tier_price_value_type" are invalid',
                ]
            ],
            [ // Confirming that ImportEmptyAttributeValueConstant is loaded
                'requestData' => [
                    'source' => [
                        'ImportEmptyAttributeValueConstant' => 'CUSTOM_EMPTY_ATTRIBUTE_VALUE_CONSTANT',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/products_custom_empty_attribute.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 3'
                ]
            ],
            [ // Verifying that incorrect ImportEmptyAttributeValueConstant in previous test will error
                'requestData' => [
                    'source' => [
                        'ImportEmptyAttributeValueConstant' => 'WRONG_EMPTY_ATTRIBUTE_VALUE_CONSTANT',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/products_custom_empty_attribute.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 1: Value for 'qty' attribute contains incorrect value",
                    1 => "Row 2: Value for 'qty' attribute contains incorrect value",
                    2 => "Row 3: Value for 'qty' attribute contains incorrect value",
                ]
            ],
            [  // Tests validation-skip-errors in case there is a fatal error
                'requestData' => [
                    'source' => [
                        'ImportFieldSeparator' => ';',
                        'entity' => 'advanced_pricing',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-skip-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/advanced_pricing.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 1: We can't find required columns: sku.",
                    1 => 'Row 1: Column names: "sku,tier_price_website,tier_price_customer_group,'
                        . 'tier_price_qty,tier_price,tier_price_value_type" are invalid',
                ]
            ],
            [ // Tests validation-skip-errors in case all are rows are invalid
                'requestData' => [
                    'source' => [
                        'ImportEmptyAttributeValueConstant' => 'WRONG_EMPTY_ATTRIBUTE_VALUE_CONSTANT',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-skip-errors',
                        'allowedErrorCount' => '10',
                        'csvData' => __DIR__ . '/_files/products_custom_empty_attribute.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 1: Value for 'qty' attribute contains incorrect value",
                    1 => "Row 2: Value for 'qty' attribute contains incorrect value",
                    2 => "Row 3: Value for 'qty' attribute contains incorrect value",
                ]
            ],
            [ // Tests validation-skip-errors in case at least one row is valid
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-skip-errors',
                        'allowedErrorCount' => '0', // 0 is intended to highlight that this value does not matter
                        'csvData' => __DIR__ . '/_files/products_with_invalid_row.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 2'
                ]
            ],
            [ // Tests that import fails if the correct locale is specified
                'requestData' => [
                    'source' => [
                        'locale' => 'en_US',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'csvData' => __DIR__ . '/_files/products_locale_de_de.csv'
                    ]
                ],
                'expectedResponse' => [
                    "Row 1: Value for 'visibility' attribute contains incorrect value," .
                    " see acceptable values on settings specified for Admin",
                    "Row 1: Value for 'gift_message_available' attribute contains incorrect value," .
                    " see acceptable values on settings specified for Admin",
                    "Row 1: Value for 'msrp_display_actual_price_type' attribute contains incorrect value," .
                    " see acceptable values on settings specified for Admin",
                    "Row 1: Value for 'country_of_manufacture' attribute contains incorrect value," .
                    " see acceptable values on settings specified for Admin",
                    "Row 1: Value for 'options_container' attribute contains incorrect value," .
                    " see acceptable values on settings specified for Admin",
                ]
            ],
            [ // Tests that import is successful if the correct locale is specified
                'requestData' => [
                    'source' => [
                        'locale' => 'de_DE',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'csvData' => __DIR__ . '/_files/products_locale_de_de.csv'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ],
        ];
    }

    /**
     * @dataProvider importAsyncDataProvider
     * @param array $requests
     * @param array $expected
     * @return void
     * @throws LocalizedException
     */
    public function testImportAsync(array $requests, array $expected): void
    {
        array_walk(
            $requests,
            function (&$request) {
                $request['source']['csvData'] = base64_encode(file_get_contents($request['source']['csvData']));
            }
        );
        $this->_markTestAsRestOnly();
        $objectManager = Bootstrap::getObjectManager();
        $logFilePath = TESTS_TEMP_DIR . "/MessageQueueTestLog.txt";

        $params = array_merge_recursive(
            Bootstrap::getInstance()->getAppInitParams(),
            ['MAGE_DIRS' => ['cache' => ['path' => TESTS_TEMP_DIR . '/cache']]]
        );

        $this->publisherConsumerController = $objectManager->create(PublisherConsumerController::class, [
            'consumers' => ['async.operations.all'],
            'logFilePath' => $logFilePath,
            'appInitParams' => $params,
        ]);

        try {
            $this->publisherConsumerController->initialize();
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (PreconditionFailedException $e) {
            $this->fail(
                $e->getMessage()
            );
        }

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::ASYNC_PATH . self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, $requests);

        $this->assertArrayHasKey(self::BULK_UUID_KEY, $response);
        $this->assertNotNull($response[self::BULK_UUID_KEY]);
        $this->assertCount(count($requests), $response['request_items']);
        $this->assertEquals(['accepted'], array_unique(array_column($response['request_items'], 'status')));
        $this->assertFalse($response['errors']);

        $bulkStatus = $objectManager->get(BulkStatusInterface::class);
        $bulkUuid = $response[self::BULK_UUID_KEY];
        try {
            $this->publisherConsumerController->waitForAsynchronousResult(
                function ($expected) use ($requests, $bulkStatus, $bulkUuid) {
                    $statusItems = $bulkStatus->getBulkDetailedStatus($bulkUuid)->getOperationsList();
                    $success = count($statusItems) === count($requests);
                    foreach ($statusItems as $statusItem) {
                        $success = (int) $statusItem->getStatus() === OperationInterface::STATUS_TYPE_COMPLETE;
                        if ($success) {
                            $this->assertNotEmpty(
                                $statusItem->getResultSerializedData(),
                                'Expectation failed for request#' . $statusItem->getId()
                            );
                            $this->assertEquals(
                                $expected[$statusItem->getId()],
                                json_decode($statusItem->getResultSerializedData(), true),
                                'Expectation failed for request#' . $statusItem->getId()
                            );
                        }
                    }
                    return $success;
                },
                [$expected]
            );
        } catch (PreconditionFailedException $e) {
            $this->fail("Not all requests were processed");
        }
    }

    public function importAsyncDataProvider(): array
    {
        return [
            [
                [
                    [
                        'source' => [
                            'locale' => 'en_US',
                            'entity' => 'catalog_product',
                            'behavior' => 'append',
                            'validationStrategy' => 'validation-stop-on-errors',
                            'allowedErrorCount' => '0',
                            'csvData' => __DIR__ . '/_files/products_locale_de_de.csv'
                        ]
                    ],
                    [
                        'source' => [
                            'locale' => 'de_DE',
                            'entity' => 'catalog_product',
                            'behavior' => 'append',
                            'validationStrategy' => 'validation-stop-on-errors',
                            'allowedErrorCount' => '0',
                            'csvData' => __DIR__ . '/_files/products_locale_de_de.csv'
                        ]
                    ],
                ],
                [
                    [
                        "Row 1: Value for 'visibility' attribute contains incorrect value," .
                        " see acceptable values on settings specified for Admin",
                        "Row 1: Value for 'gift_message_available' attribute contains incorrect value," .
                        " see acceptable values on settings specified for Admin",
                        "Row 1: Value for 'msrp_display_actual_price_type' attribute contains incorrect value," .
                        " see acceptable values on settings specified for Admin",
                        "Row 1: Value for 'country_of_manufacture' attribute contains incorrect value," .
                        " see acceptable values on settings specified for Admin",
                        "Row 1: Value for 'options_container' attribute contains incorrect value," .
                        " see acceptable values on settings specified for Admin",
                    ],
                    [
                        'Entities Processed: 1'
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (isset($this->publisherConsumerController)) {
            $this->publisherConsumerController->stopConsumers();
            $this->publisherConsumerController = null;
        }
        parent::tearDown();
    }
}
