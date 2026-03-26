<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class StagingUpdateSalesRuleGridTest extends TestCase
{
    /**
     * @var UiComponentFactory
     */
    private $factory;

    /**
     * @var UiComponentInterface
     */
    private $component;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Bootstrap::getObjectManager()->get(UiComponentFactory::class);
        $this->component = $this->factory->create('staging_update_salesrule_grid');
    }

    #[
        AppArea('adminhtml'),
        DbIsolation(true),
        AppIsolation(true),
    ]
    public function testCorrectIndexFieldUsedInConfiguration()
    {
        $dataProvider = $this->component
            ->getComponent('staging_update_salesrule_grid_data_source')
            ->getDataProvider();
        $this->assertEquals('rule_id', $dataProvider->getConfigData()['storageConfig']['indexField']);
    }
}
