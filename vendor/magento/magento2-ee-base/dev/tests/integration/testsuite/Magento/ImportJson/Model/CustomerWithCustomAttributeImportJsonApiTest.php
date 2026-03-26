<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\ImportJsonApi\Api\Data\SourceDataInterface;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerWithCustomAttributeImportJsonApiTest extends \PHPUnit\Framework\TestCase
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
     * Test Import JSON Customer with Custom Attribute
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_custom_attribute.php
     */
    public function testExecute(): void
    {
        $expectedResponse = [
            0 => 'Entities Processed: 1'
        ];

        $items = json_decode(
            file_get_contents(__DIR__ . '/_files/customer_with_custom_attribute.json'),
            true
        );
        $this->sourceData->setLocale('en_US');
        $this->sourceData->setEntity('customer');
        $this->sourceData->setBehavior('add_update');
        $this->sourceData->setValidationStrategy('validation-stop-on-errors');
        $this->sourceData->setAllowedErrorCount("0");
        $this->sourceData->setItems($items);

        $response = $this->startImport->execute($this->sourceData);

        $this->assertEquals($expectedResponse, array_values($response));
    }
}
