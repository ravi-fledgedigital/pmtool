<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class GiftCardProductImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API GiftCard Product Import
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     */
    public function testGiftCardProductImport(): void
    {
        $this->_markTestAsRestOnly('Import JSON is exclusive to REST because the API does not support SOAP.');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ]
        ];

        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'replace', //import replaces products created by ApiDataFixture
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '0',
                'items' => __DIR__ . '/_files/giftcard_product.json'
            ]
        ];

        $expectedResponse = [
            0 => 'Entities Processed: 1'
        ];

        $expectedProductName = 'Gift Card replaced by Import';

        // perform import with replace behavior
        $requestData['source']['items'] = json_decode(file_get_contents($requestData['source']['items']));
        $response = $this->_webApiCall($serviceInfo, $requestData);

        // check import response
        $this->assertEquals($expectedResponse, array_values($response));

        // check that the giftcard product replaced by import has updated name
        $objectManager = Bootstrap::getObjectManager();
        $productInterface = $objectManager->create(ProductRepositoryInterface::class);
        $product = $productInterface->get('gift-card-with-amount');
        $updatedProductName = $product->getName();
        $this->assertEquals($expectedProductName, $updatedProductName);
    }
}
