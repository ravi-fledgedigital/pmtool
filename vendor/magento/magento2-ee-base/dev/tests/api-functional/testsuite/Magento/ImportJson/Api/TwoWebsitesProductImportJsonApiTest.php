<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class TwoWebsitesProductImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API Product Import with Two Websites
     *
     * @magentoApiDataFixture Magento/Store/_files/second_website_with_two_stores.php
     *
     * @param array $requestData
     * @param array $expectedResponse
     * @dataProvider getRequestData
     */
    public function testTwoWebsitesProductImport(array $requestData, array $expectedResponse): void
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
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_with_two_websites.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        $productsToRemove = [
            'Simple Product with Two Websites'
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
