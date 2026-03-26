<?php
namespace OnitsukaTiger\Restock\Ui\DataProvider;

use Magento\ProductAlert\Model\Stock as ProductAlertStock;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Session\SessionManagerInterface;
use OnitsukaTiger\Restock\Model\ResourceModel\Grid\CollectionFactory;

class RestockDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;
    // @codingStandardsIgnoreStart
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $restockCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $restockCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
    // @codingStandardsIgnoreEnd
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $restock) {
            $this->loadedData[$restock->getId()] = $restock->getData();
        }
        return $this->loadedData;
    }
}
