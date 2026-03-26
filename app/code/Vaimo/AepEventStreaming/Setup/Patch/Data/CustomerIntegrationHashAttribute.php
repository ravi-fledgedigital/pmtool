<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Vaimo\AepEventStreaming\Service\Customer\IntegrationHash;

class CustomerIntegrationHashAttribute implements DataPatchInterface
{
    private EavSetupFactory $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): void
    {
        $this->eavSetupFactory->create()->addAttribute(
            Customer::ENTITY,
            IntegrationHash::ATTRIBUTE_CODE,
            [
                'type' => 'varchar',
                'required' => '0',
                'visible' => '0',
                'user_defined' => '0',
                'is_unique' => '0',
                'system' => '1',
            ]
        );
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
