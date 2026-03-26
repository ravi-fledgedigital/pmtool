<?php

namespace OnitsukaTiger\Customer\Block\Widget;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Framework\View\Element\Template\Context;

use Magento\Customer\Model\Session as CustomerSession;
class ShoesSize extends \Magento\Customer\Block\Widget\AbstractWidget
{

    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'shoes_size';

    /**
     * @var AddressMetadataInterface
     */
    protected $addressMetadata;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \OnitsukaTiger\Fixture\Helper\Data
     */
    private $helper;

    private CustomerSession $customerSession;

    /**
     * ShoesSize constructor.
     * @param Context $context
     * @param AddressHelper $addressHelper
     * @param \OnitsukaTiger\Fixture\Helper\Data $helper
     * @param CustomerMetadataInterface $customerMetadata
     * @param \OnitsukaTiger\Customer\Model\Config\Source\ShoesOptions $options
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressHelper $addressHelper,
        \OnitsukaTiger\Fixture\Helper\Data $helper,
        CustomerMetadataInterface $customerMetadata,
        \OnitsukaTiger\Customer\Model\Config\Source\ShoesOptions $options,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Eav\Model\Config $eavConfig,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->options = $options;
        $this->helper = $helper;
        $this->eavConfig = $eavConfig;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('OnitsukaTiger_Customer::widget/shoes-size.phtml');
    }

    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if company attribute enabled in system
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getConfig('customer/account_information/shoes_size');
    }

    public function getOptions() {
        return $this->options->getAllOptions($this->helper->getCurrentStore()->getId());
    }


    public function getDefaultOptions() {
        $attribute = $this->eavConfig->getAttribute('customer', 'shoes_size');
        return $attribute->getDefaultValue();
    }
    /**
     * Check if company attribute marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        return  $this->getConfig('customer/account_information/shoes_size') ? ($this->getConfig('customer/account_information/shoes_size') == 'req') : false;
    }

    /**
     * Get current logged-in customer data
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCurrentCustomer()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerData();
        }
        return null;
    }
}
