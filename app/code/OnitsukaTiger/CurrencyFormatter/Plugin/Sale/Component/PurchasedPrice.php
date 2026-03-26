<?php

namespace OnitsukaTiger\CurrencyFormatter\Plugin\Sale\Component;

use Magento\Directory\Model\Currency\DefaultLocator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Ui\Component\Listing\Column\PurchasedPrice as ListingPurchasedPrice;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\CurrencyInterface;

use Zend_Currency_Exception;

/**
 * Class Price
 * @package OnitsukaTiger\CurrencyFormatter\Plugin\Sale\Component
 */
class PurchasedPrice
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * Request
     *
     * @var RequestInterface
     */
    protected $_request;

    /**
     * PurchasedPrice constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param HelperData $helperData
     * @param ResolverInterface $localeResolver
     * @param CurrencyInterface $localeCurrency
     * @param FormatInterface $localeFormat
     * @param DefaultFormat $defaultFormat
     * @param DefaultLocator $currencyLocator
     * @param RequestInterface $request
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        CurrencyInterface $localeCurrency,
        FormatInterface $localeFormat,
        DefaultLocator $currencyLocator,
        RequestInterface $request,
        OrderFactory $orderFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_localeCurrency = $localeCurrency;
        $this->_orderFactory = $orderFactory;
        $this->_request = $request;
    }

    /**
     * @param ListingPurchasedPrice $subject
     * @param callable $proceed
     * @param array $dataSource
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws Zend_Currency_Exception
     */
    public function aroundPrepareDataSource(ListingPurchasedPrice $subject, callable $proceed, array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $action = $this->_request->getFullActionName();
                /**
                 * Format currency only when the last action of grid rendering is called
                 * Last action name is 'mui_index_render'
                 */
                if ($action === 'mui_index_render') {
                    $orderId = isset($item['order_id']) ? $item['order_id'] : $item['entity_id'];
                    $order = $this->_orderFactory->create()->load($orderId);
                    $storeId = $order->getStoreId();

                    $currencyCode = isset($item['order_currency_code'])
                        ? $item['order_currency_code']
                        : $item['base_currency_code'];
                    $itemName = $subject->getData('name');
                    $value = $item[$itemName];

                    $price = sprintf('%F', $value);
                    if($storeId == 5 && ($itemName == 'base_grand_total' || $itemName ==  'grand_total')){
                        $options['precision'] = 0;
                        $item[$itemName] = $this->_localeCurrency->getCurrency($currencyCode)->toCurrency($price, $options);
                    }else{
                        $options['precision'] = 2;
                        $item[$itemName] = $this->_localeCurrency->getCurrency($currencyCode)->toCurrency($price, $options);
                    }
                }
            }
        }

        return $dataSource;
    }
}
