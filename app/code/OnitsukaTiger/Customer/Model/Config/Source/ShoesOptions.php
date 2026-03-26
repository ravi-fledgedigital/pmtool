<?php

namespace OnitsukaTiger\Customer\Model\Config\Source;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Custom Attribute Renderer
 */

class ShoesOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource

{

    /**
     * @var OptionFactory
     */

    protected $optionFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $_attrOptionCollectionFactory;


    private $_session;

    /**
     * @var \Magento\Framework\App\State
     */
    private $_state;

    /**
     * @var \OnitsukaTiger\Fixture\Helper\Data
     */
    private $helper;


    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\State $state,
        \OnitsukaTiger\Fixture\Helper\Data $helper,
        \Magento\Eav\Model\Config $eavConfig
    ){
        $this->eavConfig = $eavConfig;
        $this->_attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->_state = $state;
        $this->helper = $helper;
        $this->_session = $context;
    }


    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getArea()
    {
        return $this->_state->getAreaCode();
    }

    /**
     * @param null $storeId
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getAllOptions($storeId = null)
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'shoes_size');
        if(!$storeId) {
            if($this->_state->getAreaCode()=='adminhtml') {
                if($this->_session && $this->_session instanceof \Magento\Backend\App\Action\Context){
                    if($customerData = $this->_session->getSession()->getCustomerData()) {
                        if(array_key_exists('account',$customerData)) {
                            if(array_key_exists('store_id',$customerData['account'])) {
                                $storeId = $customerData['account']['store_id'];
                            }
                        }
                    }
                }
            }
            elseif ($this->_state->getAreaCode()=='frontend') {
                $storeId = $this->helper->getCurrentStore()->getId();
            }
        }
        $valuesCollection = $this->_attrOptionCollectionFactory->create()->setAttributeFilter($attribute->getId())->setStoreFilter($storeId,false)->load();
        $optionsValue[] = ['label'=>__('Select Shoes Size'), 'value'=>'1'];
        foreach ($valuesCollection as $item) {
            $optionsValue[] = ['value'=> $item->getId(), 'label'=>$item->getValue()];
        }
        $this->_options = $optionsValue;

        return $this->_options;
    }
}
