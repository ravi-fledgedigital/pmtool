<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\Restock\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PriceFormat extends Column
{
    private $storeManager;
    private $currencyFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['product_price']) && isset($item['store_id'])) {
                    $storeId = $item['store_id'];
                    $currencySymbol = $this->getCurrencySymbolByStoreId($storeId);
                    $item['product_price'] = $currencySymbol . $item['product_price'];
                }
            }
        }
        return $dataSource;
    }

    private function getCurrencySymbolByStoreId($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $currencyCode = $store->getCurrentCurrencyCode();
            $currency = $this->currencyFactory->create()->load($currencyCode);
            return $currency->getCurrencySymbol() ?: $currencyCode;
        } catch (\Exception $e) {
            return '';
        }
    }
}
