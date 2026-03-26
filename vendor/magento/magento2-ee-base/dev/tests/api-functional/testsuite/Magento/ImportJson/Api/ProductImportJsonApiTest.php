<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\AsynchronousOperations\Api\BulkStatusInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
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
class ProductImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';
    private const ASYNC_PATH = '/async/bulk';
    private const BULK_UUID_KEY = 'bulk_uuid';

    /**
     * @var PublisherConsumerController
     */
    private $publisherConsumerController;

    /**
     * Test Rest API Product Import
     *
     * @param array $requestData
     * @param array $expectedResponse
     * @dataProvider getRequestData
     */
    public function testProductImport(array $requestData, array $expectedResponse): void
    {
        $this->_markTestAsRestOnly('Import JSON is exclusive to REST because the API does not support SOAP.');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ]
        ];
        $requestData['source']['items'] = json_decode(file_get_contents($requestData['source']['items']));
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
            [   // Imports a simple product with images
                'requestData' => [
                    'source' => [
                        'locale' => 'en_EN',
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-skip-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_with_images.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ],
            [   // Imports a simple product with non-existing website
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_with_non_existing_website.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Row 1: Invalid value in Website column (website does not exist?)'
                ]
            ],
            [   // Imports a simple product with non-existing multi-level categories
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_with_non_existing_category.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ],
            [   // Tries to replace non-existing simple product
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'replace',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/non_existing_simple_product.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Row 1: Product with specified SKU not found'
                ]
            ],
            [   // Tries to delete non-existing simple product
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'delete',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/non_existing_simple_product.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Row 1: Product with specified SKU not found'
                ]
            ],
            [   // Nothing imported because at least one error was found (validation errors not skipped)
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/two_products_one_broken.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 2: Value for 'price' attribute contains incorrect value"
                ]
            ],
            [   // Valid product is imported and the broken product is skipped (validation errors skipped)
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-skip-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/two_products_one_broken.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Entities Processed: 2"
                ]
            ],
            [   // No products imported (error limit is reached)
                'requestData' => [
                    'source' => [
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/two_products_one_broken.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => "Row 2: Value for 'price' attribute contains incorrect value"
                ]
            ],
            [   // Tests that import fails if the incorrect locale is specified
                'requestData' => [
                    'source' => [
                        'locale' => 'en_US', // us locale
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_locale_de_de.json' // german data
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
                        'locale' => 'de_DE', // german locale
                        'entity' => 'catalog_product',
                        'behavior' => 'append',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_locale_de_de.json' // german data
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ]
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
        $this->_markTestAsRestOnly('Import JSON is exclusive to REST because the API does not support SOAP.');

        array_walk(
            $requests,
            function (&$request) {
                $request['source']['items'] = json_decode(file_get_contents($request['source']['items']));
            }
        );
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
                            'items' => __DIR__ . '/_files/simple_product_locale_de_de.json'
                        ]
                    ],
                    [
                        'source' => [
                            'locale' => 'de_DE',
                            'entity' => 'catalog_product',
                            'behavior' => 'append',
                            'validationStrategy' => 'validation-stop-on-errors',
                            'allowedErrorCount' => '0',
                            'items' => __DIR__ . '/_files/simple_product_locale_de_de.json'
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

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        $productsToRemove = [
            'Simple Product with Images',
            'Simple product with multi-level categories',
            'Simple Product Valid',
            'Simple German Locale'
        ];

        $objectManager = Bootstrap::getObjectManager();
        $registry = $objectManager->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $productRepository = $objectManager->create(ProductRepository::class);

        foreach ($productsToRemove as $productSku) {
            try {
                $product = $productRepository->get($productSku);
            } catch (NoSuchEntityException $e) {
                continue;
            }
            $productRepository->delete($product);
        }

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);

        parent::tearDownAfterClass();
    }
}
