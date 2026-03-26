<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\Converter;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class GetOptionLabels
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function execute(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['label' => $this->wrapTableName('eav_attribute_option_value')],
                ['option_id', 'store_id', 'value']
            )
            ->joinInner(
                ['option' => $this->wrapTableName('eav_attribute_option')],
                'label.option_id = option.option_id',
                []
            )
            ->where('option.attribute_id in (?)', $this->getAttributeIdsSelect());

        return $this->prepareLabels($connection->fetchAll($select));
    }

    private function prepareLabels(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $optionId = (int) $row['option_id'];
            if (!isset($result[$optionId])) {
                $result[$optionId] = [];
            }

            $result[$optionId][(int) $row['store_id']] = $row['value'];
        }

        return $result;
    }

    private function getAttributeIdsSelect(): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['attr' => $this->wrapTableName('eav_attribute')], [AttributeInterface::ATTRIBUTE_ID])
            ->joinInner(
                ['entity_type' => $this->wrapTableName('eav_entity_type')],
                'attr.entity_type_id = entity_type.entity_type_id',
                []
            )
            ->where('entity_type.entity_type_code = ?', EntityResource::ENTITY_TYPE_CODE);
    }

    private function wrapTableName(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }
}
