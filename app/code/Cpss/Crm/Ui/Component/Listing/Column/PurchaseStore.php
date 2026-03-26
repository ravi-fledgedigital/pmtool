<?php
namespace Cpss\Crm\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Cpss\Crm\Model\RealStoreFactory;

class PurchaseStore extends Column
{
    private $realStoreFactory;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RealStoreFactory   $realStoreFactory
     * @param array              $components
     * @param array              $data
     * @param string             $editUrl
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RealStoreFactory $realStoreFactory,
        array $components = [],
        array $data = []
    ) {
        $this->realStoreFactory = $realStoreFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $realStores = $this->realStoreFactory->create()->getCollection();

        $stores = [];
        foreach($realStores->getData() as $store){
            $stores[$store['shop_id']] = $store['shop_name'];
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $item['store_id'] = $stores[$item['store_id']];
                }
            }
        }

        return $dataSource;
    }
}