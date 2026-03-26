<?php
namespace Cpss\Crm\Ui\Component\Listing\Column\PurchaseStore;

use Magento\Framework\Data\OptionSourceInterface;
use Cpss\Crm\Model\RealStoreFactory;

class Options implements OptionSourceInterface
{
    protected $realStoreFactory;

    public function __construct(
        RealStoreFactory $realStoreFactory
    ) {
        $this->realStoreFactory = $realStoreFactory;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $stores = $this->realStoreFactory->create()->getCollection()->getData();
        foreach($stores as $store) {
            $data['value'] = $store['shop_id'];
            $data['label'] = $store['shop_name'];
            $options[] = $data;
        }

        return $options;
    }
}