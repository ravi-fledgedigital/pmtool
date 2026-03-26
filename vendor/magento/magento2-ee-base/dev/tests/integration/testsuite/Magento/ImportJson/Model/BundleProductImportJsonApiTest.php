<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ImportJsonApi\Api\Data\SourceDataInterface;
use Magento\TestFramework\Helper\Bootstrap;

class BundleProductImportJsonApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StartImport
     */
    private $startImport;

    /**
     * @var SourceDataInterface
     */
    private $sourceData;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->startImport = $objectManager->create(StartImport::class);
        $this->sourceData = $objectManager->create(SourceDataInterface::class);
    }

    /**
     * Test Rest API Bundle Product Import
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_dropdown_options.php
     */
    public function testBundleProductImport(): void
    {
        $expectedProductName = 'Bundle Product replaced by Import';
        $expectedChildrenSku = [
            0 => 'simple-1',
            1 => 'simple2'
        ];
        $expectedResponse = [
            0 => 'Entities Processed: 3'
        ];

        $items = json_decode(
            file_get_contents(__DIR__ . '/_files/bundle_product.json'),
            true
        );
        $this->sourceData->setLocale('en_US');
        $this->sourceData->setEntity('catalog_product');
        $this->sourceData->setBehavior('replace');
        $this->sourceData->setValidationStrategy('validation-stop-on-errors');
        $this->sourceData->setAllowedErrorCount("0");
        $this->sourceData->setItems($items);

        $response = $this->startImport->execute($this->sourceData);

        $this->assertEquals($expectedResponse, array_values($response));

        // check that the bundle product replaced by import has updated name
        $objectManager = Bootstrap::getObjectManager();
        $productInterface = $objectManager->create(ProductRepositoryInterface::class);
        $product = $productInterface->get('bundle-product-dropdown-options');
        $updatedProductName = $product->getName();
        $this->assertEquals($expectedProductName, $updatedProductName);

        // check that the bundle product replaced by import has children
        $bundleProductLinkManagementInterface = $objectManager->create(ProductLinkManagementInterface::class);
        $bundleProductChildren = $bundleProductLinkManagementInterface->
            getChildren('bundle-product-dropdown-options');
        foreach ($bundleProductChildren as $key => $child) {
            $childSku = $child->getSku();
            $this->assertEquals($expectedChildrenSku[$key], $childSku);
        }
    }
}
