<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;

class FilterPositionDataPatch implements DataPatchInterface
{
    private ResourceConnection $resource;
    private AttributeConfigRepository $attributeConfigRepository;

    public function __construct(
        ResourceConnection $resource,
        AttributeConfigRepository $attributeConfigRepository
    ) {
        $this->resource = $resource;
        $this->attributeConfigRepository = $attributeConfigRepository;
    }

    public function apply(): void
    {
        $connection = $this->resource->getConnection();
        $configTable = $this->resource->getTableName('core_config_data');
        $configPath = 'mst_nav/horizontal_bar/filters';

        $select = $connection->select()
            ->from($configTable, ['value'])
            ->where('path = ?', $configPath)
            ->order('config_id DESC')
            ->limit(1);

        $jsonConfig = $connection->fetchOne($select);

        if (!$jsonConfig) {
            return;
        }

        $filters = json_decode($jsonConfig, true);
        if (!is_array($filters)) {
            return;
        }

        foreach ($filters as $filter) {
            $attributeCode = $filter['attribute_code'] ?? null;
            $position = $filter['position'] ?? null;

            if (!$attributeCode || !$position) {
                continue;
            }

            $configModel = $this->attributeConfigRepository->getByAttributeCode($attributeCode, false);

            if ($configModel) {
                $configModel->setConfigData('filter_position', $position);
            } else {
                $configModel = $this->attributeConfigRepository->create();
                $configModel->setAttributeCode($attributeCode);
                $configModel->setConfigData('filter_position', $position);
            }

            $this->attributeConfigRepository->save($configModel);
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
