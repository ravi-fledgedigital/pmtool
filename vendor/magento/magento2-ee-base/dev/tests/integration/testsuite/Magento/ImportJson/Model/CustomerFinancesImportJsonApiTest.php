<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\ImportJsonApi\Api\Data\SourceDataInterface;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerFinancesImportJsonApiTest extends \PHPUnit\Framework\TestCase
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
     * Test Import JSON Customer Finances
     * @magentoDataFixture Magento/Reward/_files/customer_with_five_reward_points.php
     */
    public function testExecute(): void
    {
        $expectedResponse = [
            0 => 'Entities Processed: 1'
        ];

        $items = json_decode(
            file_get_contents(__DIR__ . '/_files/customer_finances.json'),
            true
        );
        $this->sourceData->setLocale('en_US');
        $this->sourceData->setEntity('customer_finance');
        $this->sourceData->setBehavior('add_update');
        $this->sourceData->setValidationStrategy('validation-stop-on-errors');
        $this->sourceData->setAllowedErrorCount("0");
        $this->sourceData->setItems($items);

        $response = $this->startImport->execute($this->sourceData);

        $this->assertEquals($expectedResponse, array_values($response));
    }
}
