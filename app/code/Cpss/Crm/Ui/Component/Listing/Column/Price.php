<?php

namespace Cpss\Crm\Ui\Component\Listing\Column;

use Magento\Directory\Model\Currency;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Price extends Column
{
    protected $priceFormatter;
    private $currency;
    private $storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        array $components = [],
        array $data = [],
        Currency $currency = null,
        StoreManagerInterface $storeManager = null
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->currency = $currency ?: ObjectManager::getInstance()
            ->get(Currency::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
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
            foreach ($dataSource['data']['items'] as &$item) {
                $currencyCode = isset($item['base_currency_code']) ? $item['base_currency_code'] : null;
                if (!$currencyCode) {
                    $store = $this->storeManager->getStore(
                        $this->context->getFilterParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                    );
                    $currencyCode = $store->getBaseCurrency()->getCurrencyCode();
                }
                $tax = 0;
                $grid = isset($this->getData('js_config')['extends']) ? $this->getData('js_config')['extends'] : '';
                $basePurchaseCurrency = $this->currency->load($currencyCode);

                /*if ($grid == "admincrm_receipt_listing" && ($this->getData('name') == 'tax_amount' || $this->getData('name') == 'total_amount_incl_tax' || $this->getData('name') == 'discount_amount_incl_tax')) {
                    $item[$this->getData('name')] = $basePurchaseCurrency
                    ->format(($item[$this->getData('name')]), [], false);
                }*/
            }
        }

        return $dataSource;
    }
}
