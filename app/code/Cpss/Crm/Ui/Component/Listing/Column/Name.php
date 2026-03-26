<?php
namespace Cpss\Crm\Ui\Component\Listing\Column;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Model\Customer;

class Name extends Column
{
    private $storeManager;
    private $customer;
    
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Customer $customer,
        array $components = [],
        array $data = [],
        StoreManagerInterface $storeManager = null
    ) {
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
        $this->customer = $customer;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                try {
                    $customerData = $this->customer->load($item[$this->getData('name')]);
                    $item[$this->getData('name')] = $customerData->getName();
                } catch(\Exception $e){ }
            }
        }

        return $dataSource;
    }
}
