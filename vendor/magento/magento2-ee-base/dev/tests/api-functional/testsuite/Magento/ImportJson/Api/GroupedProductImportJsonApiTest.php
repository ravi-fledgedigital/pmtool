<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\GroupedProduct\Model\Product\Link\CollectionProvider\Grouped;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class GroupedProductImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API Grouped Product Import
     * @magentoApiDataFixture Magento/GroupedProduct/_files/product_grouped.php
     *
     */
    public function testGroupedProductImport(): void
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
                'items' => __DIR__ . '/_files/grouped_product.json'
            ]
        ];

        $expectedResponse = [
            0 => 'Entities Processed: 3'
        ];

        $expectedGroupedProductName = 'Grouped Product replaced by Import';

        $expectedAssociatedProductsSku = [
            0 => 'simple',
            1 => 'virtual-product',
        ];

        // perform import with replace behavior
        $requestData['source']['items'] = json_decode(file_get_contents($requestData['source']['items']));
        $response = $this->_webApiCall($serviceInfo, $requestData);

        // check import response
        $this->assertEquals($expectedResponse, array_values($response));

        // check that grouped product replaced by import has updated name
        $objectManager = Bootstrap::getObjectManager();
        $productInterface = $objectManager->create(ProductRepositoryInterface::class);
        $groupedProduct = $productInterface->get('grouped-product');
        $updatedGroupedProductName = $groupedProduct->getName();
        $this->assertEquals($expectedGroupedProductName, $updatedGroupedProductName);

        // check that the grouped product has associated products
        $grouped = $objectManager->create(Grouped::class);
        $associatedProducts = $grouped->getLinkedProducts($groupedProduct);
        foreach ($associatedProducts as $key => $associatedProduct) {
            $associatedProductsSku = $associatedProduct->getSku();
            $this->assertEquals($expectedAssociatedProductsSku[$key], $associatedProductsSku);
        }
    }
}
