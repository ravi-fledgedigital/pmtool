<?php

namespace OnitsukaTiger\Customer\Block\Widget;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Framework\View\Element\Template\Context;

class PersonalInformation extends \Magento\Customer\Block\Widget\AbstractWidget
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'personal_information';

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

    /**
     * PersonalInformation constructor.
     * @param Context $context
     * @param AddressHelper $addressHelper
     * @param \OnitsukaTiger\Fixture\Helper\Data $helper
     * @param CustomerMetadataInterface $customerMetadata
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressHelper $addressHelper,
        \OnitsukaTiger\Fixture\Helper\Data $helper,
        CustomerMetadataInterface $customerMetadata,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->eavConfig = $eavConfig;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // cell personal information template location
        $this->setTemplate('OnitsukaTiger_Customer::widget/personal-information.phtml');
    }
}
