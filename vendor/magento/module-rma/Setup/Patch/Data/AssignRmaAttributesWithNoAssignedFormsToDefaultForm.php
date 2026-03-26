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

namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Find all non-system RMA attributes and assign them to default form if they are not assigned to any form.
 */
class AssignRmaAttributesWithNoAssignedFormsToDefaultForm implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
            AddRmaAttributes::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()
            ->from(
                ['eav' => $this->moduleDataSetup->getTable('magento_rma_item_eav_attribute')],
                ['attribute_id']
            )
            ->joinLeft(
                ['from' => $this->moduleDataSetup->getTable('magento_rma_item_form_attribute')],
                'eav.attribute_id = from.attribute_id',
                []
            )
            ->where('eav.is_system = 0')
            ->where('from.form_code IS NULL');

        $data = array_map(
            fn ($attributeId) => ['form_code' => 'default', 'attribute_id' => $attributeId],
            $connection->fetchCol($select)
        );

        if ($data) {
            $connection->insertMultiple(
                $this->moduleDataSetup->getTable('magento_rma_item_form_attribute'),
                $data
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }
}
