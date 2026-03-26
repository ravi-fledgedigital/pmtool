<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit;

class Js extends \Magento\Backend\Block\Template
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider
     */
    private $inputTypeProvider;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider $inputTypeProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->inputTypeProvider = $inputTypeProvider;
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType[]|array
     */
    public function getAttributeInputTypes()
    {
        return $this->inputTypeProvider->getList();
    }

    /**
     * @return array
     */
    public function getAttributeInputTypesWithOptions()
    {
        return $this->inputTypeProvider->getInputTypesWithOptions();
    }

    /**
     * @param mixed $row
     *
     * @return string
     */
    public function encode($row)
    {
        return \Laminas\Json\Json::encode($row);
    }
}
