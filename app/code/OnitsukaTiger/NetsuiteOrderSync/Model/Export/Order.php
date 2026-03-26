<?php
namespace OnitsukaTiger\NetsuiteOrderSync\Model\Export;

use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\Export\Dependencies\Config as ExportConfig;
use Firebear\ImportExport\Model\ExportJob\Processor;
use DateTime;
use Exception;
use Firebear\ImportExport\Model\ResourceModel\Export\History as ExportHistory;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use OnitsukaTiger\NetsuiteOrderSync\Plugin\Controller\Adminhtml\Job\FilterStockLocation;

class Order extends \Firebear\ImportExport\Model\Export\Order {

    const SHIPMENT_STOCK_LOCATION = 'shipment:stock_location';
    const SHIPMENT_STATUS = 'shipment:shipment_status';

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param OrderCollectionFactory $orderColFactory
     * @param ResourceConnection $resource
     * @param ExportConfig $exportConfig
     * @param SourceFactory $sourceFactory
     * @param Helper $helper
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Json $jsonSerializer
     * @param CustomerFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        LoggerInterface $logger,
        ConsoleOutput $output,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        OrderCollectionFactory $orderColFactory,
        ResourceConnection $resource,
        ExportConfig $exportConfig,
        SourceFactory $sourceFactory,
        Helper $helper,
        StatusCollectionFactory $statusCollectionFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Json $jsonSerializer,
        CustomerFactory $customerFactory,
        ModuleManager $moduleManager,
        FilterBuilder $filterBuilder,
        ExportHistory $exportHistoryResource,
        array $data = []
    ) {
        $this->shipmentRepository = $shipmentRepository;
        parent::__construct($logger, $output, $scopeConfig, $storeManager, $collectionFactory, $resourceColFactory,
            $orderColFactory, $resource, $exportConfig, $sourceFactory, $helper, $statusCollectionFactory,
            $productRepository, $searchCriteriaBuilder, $jsonSerializer, $customerFactory, $moduleManager, $filterBuilder, $exportHistoryResource, $data);
    }

    /**
     * @param array $data
     * @param string $table
     * @return array
     * @throws Exception
     */
    protected function _updateData($data, $table)
    {
        if (!isset($this->_parameters[Processor::EXPORT_FILTER_TABLE]) ||
            !is_array($this->_parameters[Processor::EXPORT_FILTER_TABLE])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Processor::EXPORT_FILTER_TABLE];
        }

        $filters = [];
        $prefix = $this->_prefixData[$table] ?? '';

        foreach ($exportFilter as $filter) {
            if ($filter['entity'] == $table) {
                $field = $prefix ? $prefix . ':' . $filter['field'] : $filter['field'];
                $filters[$field] = $filter['value'];
            }
        }

        if (empty($this->_describeTable[$table])) {
            $this->_describeTable[$table] = $this->_connection->describeTable(
                $this->_resourceModel->getTableName($table)
            );
        }

        $info = [];
        foreach ($this->_describeTable[$table] as $field => $fieldInfo) {
            if ($prefix) {
                $field = $prefix . ':' . $field;
            }
            $info[$field] = $fieldInfo;
        }

        foreach ($data as $field => $value) {
            $dataType = $info[$field]['DATA_TYPE'] ?? null;
            $type = $dataType ? $this->_helper->convertTypesTables($dataType) : null;

            if (!empty($data['shipment:entity_id'])) {
                $shipment = $this->shipmentRepository->get($data['shipment:entity_id']);
            }

            if($field == self::SHIPMENT_STOCK_LOCATION) {
                if (!empty($data['shipment:entity_id'])) {
                    $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                    $data[self::SHIPMENT_STOCK_LOCATION] = $sourceCode;
                }
            }else if($field == self::SHIPMENT_STATUS) {
                if (!empty($data['shipment:entity_id'])) {
                    $shipmentStatus = $shipment->getExtensionAttributes()->getStatus();
                    $data[self::SHIPMENT_STATUS] = $shipmentStatus;
                }
            }

            if ('sales_order' != $table && isset($filters[$field])) {
                if (!isset($this->filters[$table])) {
                    $this->filters[$table] = [];
                }

                if ($field == self::SHIPMENT_STOCK_LOCATION) {
                    if (!empty($data['shipment:entity_id'])) {
                        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                        $value = $sourceCode;
                    }
                }else if($field == self::SHIPMENT_STATUS) {
                    if (!empty($data['shipment:entity_id'])) {
                        $shipmentStatus = $shipment->getExtensionAttributes()->getStatus();
                        $data[self::SHIPMENT_STATUS] = $shipmentStatus;
                    }
                }

                if (empty($this->filters[$table][$field])) {
                    $isValid = false;
                    $filterValue = $filters[$field];
                    if ('text' == $type) {
                        if (is_scalar($filterValue)) {
                            trim($filterValue);
                        }
                        $isValid = mb_stripos($value, $filterValue) !== false;

                        if ($field == self::SHIPMENT_STOCK_LOCATION) {
                            $data[FilterStockLocation::STOCK_LOCATION] = $filterValue;
                        }
                    } elseif ('int' == $type) {
                        if (is_array($filterValue) && count($filterValue) == 2) {
                            $from = array_shift($filterValue);
                            $to = array_shift($filterValue);
                            $isValid = $from <= $value && ($to === '' || $to >= $value);
                        } else {
                            $isValid = mb_stripos($value, $filterValue) !== false;
                        }
                    } elseif ('date' == $type) {
                        if (is_array($filterValue) && count($filterValue) == 2) {
                            $from = array_shift($filterValue);
                            $to = array_shift($filterValue);
                            if ($value && $from && $to) {
                                $value = (new DateTime($value));
                                $from = (new DateTime($from));
                                $to = (new DateTime($to));
                                $isValid = ($to >= $value) && ($from <= $value);
                            }
                        }
                    }
                    $this->filters[$table][$field] = $isValid;
                }
            }

            if (in_array($dataType, ['blob', 'mediumblob', 'tinyblob', 'longblob'])) {
                $data[$field] = !empty($value) ? base64_encode($value) : $value;
            }
        }

        $instr = $this->_scopeFields($table);
        $allFields = $this->_parameters['all_fields'];
        if (!$allFields) {
            return $this->_changedColumns($data, $instr, $table);
        }
        return $this->_addPartColumns($data, $instr, $table);
    }

    /**
     * @param $data
     * @param $instr
     * @param $table
     *
     * @return array
     */
    protected function _addPartColumns($data, $instr, $table)
    {
        $newData = [];
        $prefix = $this->_prefixData[$table] ?? '';

        foreach ($instr['list'] as $k => $code) {
            $newCode = $code;
            $hasPrefix = $code;
            if (isset($instr['replaces'][$k])) {
                $newCode = $instr['replaces'][$k];
            }
            if(strpos($code,":")){
                $code = explode(":", $code)[1];
            }
            $newData[$newCode] = $data[$code] ?? '';
            try {
                if ($table !== 'sales_order' && strpos($hasPrefix, $prefix) !== false) {
                    $newData[$newCode] = $data[$code] ?? '';
                    if (empty($newData[$newCode])) {
                        $codekey = str_replace($prefix.":", "", $code);
                        $newData[$newCode] = $data[$codekey] ?? '';
                    }
                } elseif ($prefix) {
                    $newData[$newCode] = $data[$prefix . ':' . $code] ?? '';
                }
            } catch (Exception $exception) {
                $this->addLogWriteln($code, $this->getOutput(), 'error');
            }
            if (isset($instr['replacesValues'][$k])
                && !empty($instr['replacesValues'][$k])) {
                $newData[$newCode] = $instr['replacesValues'][$k];
            }
        }

        return $newData;
    }

    /**
     * Prepare child entity
     *
     * @param array  $entityIds
     * @param string $table
     * @param int $parentIdField
     * @param int $entityIdField
     * @param array $customerId
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    protected function _prepareChildEntity($entityIds, $table, $parentIdField, $entityIdField, $customerId = [])
    {
        $rowId = 0;
        $initialEntityData = $this->_exportBaseData;
        if ($table === 'customer_entity') {
            if (empty($customerId)) {
                return;
            }
            $entityIds = [$customerId];
        }
        $select = $this->_connection->select()->from(
            $this->_resourceModel->getTableName($table)
        )->where(
            $parentIdField . ' IN (?)',
            $entityIds
        );
        $stmt = $this->_connection->query($select);
        $prefix = $this->_prefixData[$table] ?? $table;
        $prefix2 = '';
        $isOneLine = $this->_parameters['behavior_data']['export_in_one_line'] ?? 0;

        $deps = $this->_parameters['behavior_data']['deps'];
        $children = $this->_exportConfig['order']['fields'] ?? [];
        $entityIds = [];
        $productIds = [];
        $orderItemAndProductIdPairs = [];
        $this->prepareCustomer($customerId);
        if ($this->_isNested()) {
            $exportData = [];
            while ($row = $stmt->fetch()) {
                $entityIds[] = $row[$entityIdField];
                if ($table == 'sales_order_item') {
                    $orderItemAndProductIdPairs[$row['item_id']] = $row['product_id'];
                }

                foreach ($row as $column => $value) {
                    if ($table == 'sales_order_address') {
                        if ($column == 'street') {
                            $row = $this->prepareStreetFields($row, '');
                        }
                    }
                }

                $exportData[] = ['item' => $this->_updateData($row, $table)];
            }
            $this->_exportData[0][$prefix] = $exportData;
        } else {
            while ($row = $stmt->fetch()) {
                $entityIds[] = $row[$entityIdField];
                if ($table == 'sales_order_item') {
                    $orderItemAndProductIdPairs[$row['item_id']] = $row['product_id'];
                    $row['downloadable_link_data'] = '';
                    if (!empty($row['product_type']) &&
                        $row['product_type'] == 'downloadable' &&
                        !empty($row['item_id'])) {
                        $row['downloadable_link_data'] = $this->jsonSerializer->serialize(
                            $this->getDownloadableItemData($row['item_id'])
                        );
                    }
                }
                if ($table == 'sales_order_address'
                    && isset($row[OrderAddressInterface::ADDRESS_TYPE])
                    && $isOneLine
                ) {
                    $addressType = $row[OrderAddressInterface::ADDRESS_TYPE];
                    $prefix2 = $addressType;
                } elseif ($table == 'sales_order_address'
                    && isset($row[OrderAddressInterface::ADDRESS_TYPE])) {
                    $prefix2 = 'address';
                }
                foreach ($row as $column => $value) {
                    if ($table == 'sales_order_address') {
                        if ($column == 'street') {
                            $row = $this->prepareStreetFields($row, $prefix2);
                        } else {
                            $row[$prefix2 . ':' . $column] = $value;
                        }
                    } else {
                        $row[$prefix . ':' . $column] = $value;
                    }
                    unset($row[$column]);
                }
                $row = $this->_updateData($row, $table);
                $exportData = $this->_exportData[$rowId] ?? [];
                /*if ($rowId) {
                    $initialEntityData['line_type'] = '';
                } else {
                    $initialEntityData['line_type'] = 'order';
                }*/

                $initialEntityData = $this->sortStreetFields($initialEntityData);

                $this->_exportData[$rowId] = array_merge($initialEntityData, $exportData, $row);
                if ($table != 'sales_order_address' || !$isOneLine) {
                    $rowId++;
                } else {
                    $initialEntityData = array_merge($initialEntityData, $row);
                }
            }
        }
        if (!empty($orderItemAndProductIdPairs)) {
            ksort($orderItemAndProductIdPairs);
            $productIds = array_values($orderItemAndProductIdPairs);
        }

        if (!count($entityIds)) {
            if (!isset($this->_parameters[Processor::EXPORT_FILTER_TABLE]) ||
                !is_array($this->_parameters[Processor::EXPORT_FILTER_TABLE])) {
                $exportFilter = [];
            } else {
                $exportFilter = $this->_parameters[Processor::EXPORT_FILTER_TABLE];
            }

            foreach ($exportFilter as $filter) {
                if ($filter['entity'] == $table) {
                    $this->filters[$table] = false;
                } else {
                    foreach ($children as $childTable => $param) {
                        if ($filter['entity'] == $childTable && $param['parent'] == $table) {
                            $this->filters[$childTable] = false;
                        }
                    }
                }
            }
            return;
        }

        if (in_array($table, $deps)) {
            foreach ($children as $childTable => $param) {
                if ($param['parent'] == $table && in_array($childTable, $deps)) {
                    if ($childTable == 'sales_order_product') {
                        if (!empty($productIds)) {
                            $this->prepareProduct($productIds);
                        }
                    } elseif ($childTable === 'magento_rma_item_entity' && $this->moduleManager->isEnabled('Magento_Rma')) {
                        $this->prepareRmaItems($entityIds);
                    } else {
                        $this->_prepareChildEntity(
                            $entityIds,
                            $childTable,
                            $param['parent_field'],
                            $param['main_field']
                        );
                    }
                }
            }
        }
    }
    /**
     * Retrieve downlodable product data
     *
     * @param $itemId
     * @return array
     */
    private function getDownloadableItemData($itemId)
    {
        $select = $this->_connection->select()
            ->from(['dlp' => $this->_resourceModel->getTableName('downloadable_link_purchased')])
            ->join(['dlpi' => 'downloadable_link_purchased_item'], 'dlpi.order_item_id  = dlp.order_item_id ')
            ->where('dlp.order_item_id = ?', $itemId);
        return $this->_connection->fetchAll($select);
    }
}