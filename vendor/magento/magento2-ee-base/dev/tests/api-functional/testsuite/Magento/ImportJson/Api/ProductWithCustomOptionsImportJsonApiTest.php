<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Api;

use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class ProductWithCustomOptionsImportJsonApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/import/json';

    /**
     * Test Rest API Product with Custom Options Import
     *
     */
    public function testProductWithCustomOptionsImport(): void
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
                'behavior' => 'append',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '0',
                'items' => __DIR__ . '/_files/simple_product_with_custom_options.json'
            ]
        ];

        $expectedResponse = [
            0 => 'Entities Processed: 1'
        ];

        $expectedOptionTitles = [
            0 => 'Text Field Option Title',
            1 => 'Text Area Option Title',
            2 => 'File Option Title',
            3 => 'Select Drop Down Option Title',
            4 => 'Select Radio Buttons Option Title',
            5 => 'Select Checkbox Option Title',
            6 => 'Select Multiple Select Option Title',
            7 => 'Date Option Title',
            8 => 'Date & Time Option Title',
            9 => 'Time Option Title',
        ];

        // perform product import with append behavior
        $requestData['source']['items'] = json_decode(file_get_contents($requestData['source']['items']));
        $response = $this->_webApiCall($serviceInfo, $requestData);

        // check import response
        $this->assertEquals($expectedResponse, array_values($response));

        // check imported simple product custom options titles
        $objectManager = Bootstrap::getObjectManager();
        $productCustomOptionRepositoryInterface = $objectManager->create(ProductCustomOptionRepositoryInterface::class);
        $productCustomOptions = $productCustomOptionRepositoryInterface->getList('Simple Product with Custom Options');
        foreach ($productCustomOptions as $key => $option) {
            $optionTitle = $option->getTitle();
            $this->assertEquals($expectedOptionTitles[$key], $optionTitle);
        }
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        $productsToRemove = [
            'Simple Product with Custom Options'
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
