<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class CustomersImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API Customers Import
     * @magentoApiDataFixture Magento/Customer/_files/import_export/customers.php
     */
    public function testCustomersImport(): void
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
                'entity' => 'customer',
                'behavior' => 'add_update', //import updates customers created by ApiDataFixture
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '0',
                'items' => __DIR__ . '/_files/customers.json'
            ]
        ];

        $expectedResponse = [
            0 => 'Entities Processed: 3'
        ];

        $expectedCustomerFirstName = 'Firstname Updated by Import';

        // perform import with add_update behavior
        $requestData['source']['items'] = json_decode(file_get_contents($requestData['source']['items']));
        $response = $this->_webApiCall($serviceInfo, $requestData);

        // check import response
        $this->assertEquals($expectedResponse, array_values($response));

        // check that the customer updated by import has updated firstname
        $objectManager = Bootstrap::getObjectManager();
        $customerRepositoryInterface = $objectManager->create(CustomerRepositoryInterface::class);
        $customer = $customerRepositoryInterface->get('customer@example.com');
        $updatedCustomerFirstName = $customer->getFirstname();
        $this->assertEquals($expectedCustomerFirstName, $updatedCustomerFirstName);
    }
}
