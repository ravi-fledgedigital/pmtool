<?php

namespace OnitsukaTigerKorea\Customer\Block\Widget;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Options;
use Magento\Framework\View\Element\Template\Context;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Name
 */
class Name extends \Magento\Customer\Block\Widget\Name
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * Name constructor.
     * @param Context $context
     * @param AddressHelper $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param Options $options
     * @param AddressMetadataInterface $addressMetadata
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressHelper $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        Options $options,
        AddressMetadataInterface $addressMetadata,
        Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $addressHelper, $customerMetadata, $options, $addressMetadata, $data);
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        if ($this->dataHelper->isKoreanThemeEnable()){
            $this->setTemplate('OnitsukaTigerKorea_Customer::widget/name.phtml');
        }
    }

    /**
     * Check if attribute is required
     *
     * @param string $attributeCode
     * @return bool
     */
    private function _isAttributeRequired($attributeCode)
    {
        $attributeMetadata = $this->_getAttribute($attributeCode);
        return $attributeMetadata ? (bool)$attributeMetadata->isRequired() : false;
    }

    /**
     * Check if attribute is visible
     *
     * @param string $attributeCode
     * @return bool
     */
    private function _isAttributeVisible($attributeCode)
    {
        $attributeMetadata = $this->_getAttribute($attributeCode);
        return $attributeMetadata ? (bool)$attributeMetadata->isVisible() : false;
    }
}
