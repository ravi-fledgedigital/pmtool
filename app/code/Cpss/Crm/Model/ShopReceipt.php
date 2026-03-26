<?php
namespace Cpss\Crm\Model;

class ShopReceipt extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'sales_real_store_order';

    protected $_cacheTag = 'sales_real_store_order';

    protected $_eventPrefix = 'sales_real_store_order';

    protected $registry;

    protected $realStoreItemsFactory;

    protected function _construct()
    {
        $this->_init('Cpss\Crm\Model\ResourceModel\ShopReceipt');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Cpss\Pos\Model\PosDataFactory $realStoreItemsFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->realStoreItemsFactory = $realStoreItemsFactory;
        $this->registry = $registry;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Load real store order by purchaseId
     *
     * @param string $purchaseId
     * @return object
     */
    public function loadByPurchaseId($purchaseId)
    {
        return $this->loadByAttribute('purchase_id', $purchaseId);
    }

    /**
     * Load real store order by custom attribute value. Attribute value should be unique
     *
     * @param string $attribute
     * @param string $value
     * @return $this
     */
    public function loadByAttribute($attribute, $value)
    {
        $this->load($value, $attribute);
        return $this;
    }

    /**
     * Get Items
     *
     * @return array
     */
    public function getItems()
    {
        if ($this->getData('items') == null) {
            $itemCollection = $this->realStoreItemsFactory->create()->getCollection()
                ->addFieldToFilter('sales_real_store_order_id', $this->getId());

            $this->setData(
                'items',
                $itemCollection->getData()
            );
        }
        return $itemCollection;
    }
}