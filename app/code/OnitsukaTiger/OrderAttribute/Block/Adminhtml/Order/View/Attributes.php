<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Sales\Model\Order;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

class Attributes extends \Magento\Backend\Block\Widget
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver
     */
    private $entityResolver;

    public function __construct(
        Context $context,
        \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory $metadataFormFactory,
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver $entityResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->metadataFormFactory = $metadataFormFactory;
        $this->entityResolver = $entityResolver;
    }

    /**
     * Return array of additional account data
     * Value is option style array
     *
     * @return array
     */
    public function getOrderAttributesData()
    {
        $orderAttributesData = [];
        $entity = $this->entityResolver->getEntityByOrder($this->getOrder());
        if ($entity->isObjectNew()) {
            return [];
        }
        $form = $this->createEntityForm($entity);
        $outputData = $form->outputData(\Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_HTML);
        foreach ($outputData as $attributeCode => $data) {
            if (!empty($data)) {
                $orderAttributesData[] = [
                    'label' => $form->getAttribute($attributeCode)->getDefaultFrontendLabel(),
                    'value' => $data
                ];
            }
        }

        return $orderAttributesData;
    }

    /**
     * Return Checkout Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @return Form
     */
    protected function createEntityForm($entity)
    {
        /** @var Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode('adminhtml_order_view')
            ->setEntity($entity)
            ->setStore($this->getOrder()->getStore());

        return $formProcessor;
    }

    /**
     * @param string $label
     * @return string
     */
    public function getOrderAttributeEditLink($label = '')
    {
        $link = '';
        if ($this->isAllowedToEdit() && $this->isOrderViewPage()) {
            $label = $label ?: __('Edit');
            $url = $this->getOrderAttributeEditUrl();
            $link = sprintf('<a href="%s">%s</a>', $url, $label);
        }

        return $link;
    }

    /**
     * @return string
     */
    protected function getOrderAttributeEditUrl()
    {
        return $this->getUrl(
            'otorderattribute/order_attributes/edit',
            ['order_id' => $this->getOrder()->getId()]
        );
    }

    /**
     * @return bool
     */
    protected function isAllowedToEdit()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_OrderAttribute::attribute_value_edit');
    }

    /**
     * @return Order
     */
    protected function getOrder()
    {
        if (!$this->hasData('order_entity')) {
            $this->setData('order_entity', $this->getParentBlock()->getOrder());
        }
        return $this->getData('order_entity');
    }

    /**
     * @return boolean
     */
    public function isOrderViewPage()
    {
        return (boolean) $this->getOrderInfoArea() == 'order';
    }

    /**
     * @return bool
     */
    public function isShipmentViewPage()
    {
        return (boolean) $this->getOrderInfoArea() == 'shipment';
    }

    /**
     * @return bool
     */
    public function isInvoiceViewPage()
    {
        return (boolean) $this->getOrderInfoArea() == 'invoice';
    }
}
