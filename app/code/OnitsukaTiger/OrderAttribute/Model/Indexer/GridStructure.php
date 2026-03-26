<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\ScopeResolver\FlatScopeResolver;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\GridStructure as ParentGridStructure;

class GridStructure extends ParentGridStructure
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var FlatScopeResolver
     */
    private $flatScopeResolver;

    public function __construct(
        ResourceConnection $resource,
        FlatScopeResolver $flatScopeResolver,
        array $columnTypesMap = []
    ) {
        $this->resource = $resource;
        $this->flatScopeResolver = $flatScopeResolver;
        parent::__construct($resource, $flatScopeResolver, $columnTypesMap);
    }

    /**
     * @param string $tableName
     * @param array $fields
     * @throws \Zend_Db_Exception
     * @return void
     */
    protected function createFlatTable($tableName, array $fields)
    {
        $adapter = $this->getWriteAdapter();
        $table = $adapter->newTable($tableName);
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity ID'
        );
        $searchableFields = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'searchable') {
                $searchableFields[] = $field['name'];
            }
            $columnMap = isset($field['dataType']) && isset($this->columnTypesMap[$field['dataType']])
                ? $this->columnTypesMap[$field['dataType']]
                : ['type' => $field['dataType'], 'size' => isset($field['size']) ? $field['size'] : null];
            $name = $field['name'];
            $type = $columnMap['type'];
            $size = $columnMap['size'];
            if ($field['type'] === 'filterable') {
                $table->addIndex(
                    $this->resource->getIdxName($tableName, $name, AdapterInterface::INDEX_TYPE_INDEX),
                    $name,
                    ['type' => AdapterInterface::INDEX_TYPE_INDEX]
                );
            }
            $table->addColumn($name, $type, $size);
        }
        $adapter->createTable($table);
    }

    /**
     * @return false|AdapterInterface
     */
    private function getWriteAdapter()
    {
        return $this->resource->getConnection('write');
    }
}
