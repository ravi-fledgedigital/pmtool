<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab\Options;

class Options extends \Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Options
{
    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider
     */
    private $inputTypeProvider;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider $inputTypeProvider,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $attrOptionCollectionFactory, $universalFactory, $data);
        $this->dataObjectFactory = $dataObjectFactory;
        $this->inputTypeProvider = $inputTypeProvider;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('OnitsukaTiger_OrderAttribute::attribute/options.phtml');
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute                     $attribute
     * @param array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $optionCollection
     *
     * @return \Magento\Framework\DataObject[]
     */
    protected function _prepareOptionValues(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute,
        $optionCollection
    ) {
        $defaultValues = [];
        $inputType = '';
        if ($attribute->getFrontendInput()) {
            $inputConfig = $this->inputTypeProvider->getAttributeInputType($attribute->getFrontendInput());
            if ($inputConfig && $inputConfig->isManageOptions() && $attribute->getDefaultValue()) {
                $defaultValues = explode(',', $attribute->getDefaultValue());
                $inputType = $inputConfig->getOptionDefault();
            }
        }

        $values = [];
        $isSystemAttribute = is_array($optionCollection);
        foreach ($optionCollection as $option) {
            $bunch = $isSystemAttribute
                ? $this->_prepareSystemAttributeOptionValues(
                    $option,
                    $inputType,
                    $defaultValues
                )
                : $this->_prepareUserDefinedAttributeOptionValues(
                    $option,
                    $inputType,
                    $defaultValues
                );
            foreach ($bunch as $value) {
                $values[] = $this->dataObjectFactory->create(['data' => $value]);
            }
        }

        return $values;
    }
}
