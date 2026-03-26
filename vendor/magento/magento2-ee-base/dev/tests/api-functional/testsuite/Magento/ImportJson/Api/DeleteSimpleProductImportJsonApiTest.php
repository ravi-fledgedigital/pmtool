<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\TestCase\WebapiAbstract;

class DeleteSimpleProductImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API Product Import with Delete behavior
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @param array $requestData
     * @param array $expectedResponse
     * @dataProvider getRequestData
     */
    public function testSimpleProductDeleteByImport(array $requestData, array $expectedResponse): void
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
                        'behavior' => 'delete',
                        'validationStrategy' => 'validation-stop-on-errors',
                        'allowedErrorCount' => '0',
                        'items' => __DIR__ . '/_files/simple_product_delete.json'
                    ]
                ],
                'expectedResponse' => [
                    0 => 'Entities Processed: 1'
                ]
            ]
        ];
    }
}
