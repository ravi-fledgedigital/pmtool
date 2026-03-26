<?php

namespace OnitsukaTiger\Ninja\Model;

use Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory;

class Carrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier
{
    /**
     * @var string
     */
    protected $_defaultConditionName = 'package_value_with_discount';

    /**
     * @var string
     */
    protected $_code = 'ninja';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $quote;

    /**
     *  @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var TablerateFactory
     */
    protected $_tablerateFactory;

    /**
     * AbstractCarrier constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Backend\Model\Session\Quote $quote
     * @param \Magento\Framework\App\State $state
     * @param TablerateFactory $_tablerateFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Session\Quote $quote,
        \Magento\Framework\App\State $state,
        TablerateFactory $_tablerateFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->quote = $quote;
        $this->state = $state;
        $this->_tablerateFactory = $_tablerateFactory;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(
        \Magento\Quote\Model\Quote\Address\RateRequest $request
    )
    {
        try {
            $this->setData('store', $this->getScopeId());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->showErrorResult($e->getMessage());
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $price = 0.0;
        if (!$request->getConditionName()) {
            $conditionName = $this->getConfigData('condition_name');
            $request->setConditionName($conditionName ? $conditionName : $this->_defaultConditionName);
        }

        $rate = $this->getRate($request);
        if ($rate && $rate['price']) {
            $price = $rate['price'];
        }

        $method = $this->_rateMethodFactory->create()->setData([
            'carrier'       => $this->_code,
            'carrier_title' => $this->getConfigData('title'),
            'method'        => $this->_code,
            'method_title'  => $this->getConfigData('name'),
            'price'         => $price,
            'cost'          => $price,
        ]);

        return $result->append($method);
    }

    /**
     * getAllowedMethods
     *
     * @return  array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param string|null $errorMsg
     *
     * @return bool
     */
    private function showErrorResult($errorMsg = null)
    {
        if (!$this->getConfigData('showmethod')) {
            return false;
        }

        return $this->_rateErrorFactory->create()->setData([
            'carrier'       => $this->_code,
            'carrier_title' => $this->getConfigData('title'),
            'error_message' => $errorMsg ?: $this->getConfigData('specificerrmsg'),
        ]);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getScopeId()
    {
        if ($this->state->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $storeId = $this->quote->getStoreId();
        } else {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $scope = $this->request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?: $storeId;
        if ($website = $this->request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getWebsite($website)->getDefaultStore();
            if ($store) {
                return $store->getId();
            }
        }

        return $scope;
    }

    /**
     * Get rate.
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return array|bool
     */
    protected function getRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        return $this->_tablerateFactory->create()->getRate($request);
    }
}
