<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\DataProvider;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation\Collection;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Listing extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\Relation $relation */
        foreach ($items as $relation) {
            // load Relation Details
            $relation->loadRelationDetails();
            $this->loadedData[$relation->getId()] = $relation->getData();
        }

        $data = $this->dataPersistor->get('onitsukatiger_order_attribute_relation');
        if (!empty($data)) {
            $relation = $this->collection->getNewEmptyItem();
            $relation->setData($data);
            $this->loadedData[$relation->getId()] = $relation->getData();
            $this->dataPersistor->clear('onitsukatiger_order_attribute_relation');
        }

        return $this->loadedData;
    }
}
