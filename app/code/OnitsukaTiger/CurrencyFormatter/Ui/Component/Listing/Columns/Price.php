<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\CurrencyFormatter\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\RequestInterface;

class Price extends \Magento\Ui\Component\Listing\Columns\Column
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
     * Interface for getting request data
     *
     * @var RequestInterface
     */
    protected $_request;
    private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper;

    /**
     * Constructs a new instance.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context The context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory component
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager The store manager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency The locale currency
     * @param \Magento\Sales\Model\OrderFactory $orderFactory The order factory
     * @param \Magento\Framework\App\RequestInterface $request The request
     * @param array $components The components
     * @param array $data The data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        CurrencyInterface $localeCurrency,
        OrderFactory $orderFactory,
        \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper,
        RequestInterface $request,
        array $components = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_localeCurrency = $localeCurrency;
        $this->_orderFactory = $orderFactory;
        $this->_request = $request;

        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->helper = $helper;
    }

    /**
     * Getting data
     *
     * @param      array  $dataSource  The data source
     *
     * @return     array  ( description_of_the_return_value )
     */
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {

            foreach ($dataSource['data']['items'] as & $item) {

                $orderId = isset($item['order_id']) ? $item['order_id'] : $item['entity_id'];

                $order = $this->_orderFactory->create()->load($orderId);
                $storeId = $order->getStoreId();

                $currencyCode = isset($item['order_currency_code'])
                    ? $item['order_currency_code']
                    : $item['base_currency_code'];

                $itemName = $this->getData('name');
                $value = $item[$itemName];
                if ($storeId == 5) {

                    $item['customer_email']= $this->helper->maskEmail($item['customer_email']);
                    $item['customer_name']=  $this->helper->maskName($item['customer_name']);
                    $item['billing_name']=  $this->helper->maskName($item['billing_name']);
                    try {

                        if (isset($item['billing_address']) && !empty(trim($item['billing_address']))) {
                            $item['billing_address'] = $this->helper->maskAddress($item['billing_address']);
                        }
                        if (isset($item['shipping_address']) && !empty(trim($item['shipping_address']))) {
                            $item['shipping_address'] = $this->helper->maskAddress($item['shipping_address']);
                        }
                    } catch (\Exception $e) {
                        $e->getMessage();
                    }

                    $priceArr = explode('.', $value ?? '');
                    if (!empty($priceArr) && $priceArr[0]) {
                        $price = $priceArr[0];
                        $item[$itemName] = $price;
                    }
                } else {
                    $options['precision'] = 2;
                    $item[$itemName] = $item[$itemName];
                }
            }
        }

        return $dataSource;
    }
    /*
        public function maskKoreanAddress($address)
        {
            if (empty($address)) {
                return $address;
            }

            $maskedAddress = preg_replace_callback('/([\p{L}\p{N}])([\p{L}\p{N}]+)/u', function ($matches) {
                return $matches[1] . str_repeat('*', mb_strlen($matches[2]));
            }, $address);

            return $maskedAddress;
        }*/
}
