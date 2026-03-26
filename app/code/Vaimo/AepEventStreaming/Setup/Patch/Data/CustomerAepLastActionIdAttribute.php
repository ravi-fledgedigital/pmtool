<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterface;

class CustomerAepLastActionIdAttribute implements DataPatchInterface
{
    private EavSetupFactory $eavSetupFactory;
    private EavConfig $eavConfig;

    public function __construct(
        EavConfig $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): void
    {
        $this->eavSetupFactory->create()->addAttribute(
            Customer::ENTITY,
            IngestRecordInterface::ATTRIBUTE_CODE,
            [
                'type' => 'varchar',
                'label' => 'AEP last action ID',
                'required' => '0',
                'visible' => '1',
                'user_defined' => '0',
                'is_unique' => '0',
                'system' => '1',
                'position'   => 200,
                'sort_order' => 200,
            ]
        );

        $attribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            IngestRecordInterface::ATTRIBUTE_CODE
        );

        $attribute->setData(
            'used_in_forms',
            ['adminhtml_customer']
        );
        $attribute->save();
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
