<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Order;

use OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

class Attributes extends Template
{
    /**
     * @var FormFactory
     */
    protected $metadataFormFactory;

    /**
     * @var EntityResolver
     */
    private $entityResolver;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var string[]
     */
    private $escapedInputTypes = ['file', 'html'];

    public function __construct(
        Template\Context $context,
        FormFactory $metadataFormFactory,
        EntityResolver $entityResolver,
        Registry $coreRegistry,
        array $escapedInputTypes = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->metadataFormFactory = $metadataFormFactory;
        $this->entityResolver = $entityResolver;
        $this->coreRegistry = $coreRegistry;
        $this->escapedInputTypes = array_merge($this->escapedInputTypes, $escapedInputTypes);
    }

    /**
     * Return array of additional account data
     * Value is option style array
     *
     * @return array
     */
    public function getOrderAttributesData()
    {
        if (!$this->getOrder()) {
            return [];
        }

        $orderAttributesData = [];
        $entity = $this->entityResolver->getEntityByOrder($this->getOrder());
        if ($entity->isObjectNew()) {
            return [];
        }
        $form = $this->createEntityForm($entity);
        $outputData = $form->outputData(\Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_HTML);
        foreach ($outputData as $attributeCode => $data) {
            if (!empty($data)) {
                $attribute = $form->getAttribute($attributeCode);
                $orderAttributesData[] = [
                    'label' => $attribute->getDefaultFrontendLabel(),
                    'value' => (in_array($attribute->getFrontendInput(), $this->escapedInputTypes, true))
                        ? $data
                        : nl2br($this->escapeHtml($data))
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
        $formProcessor->setFormCode('frontend_order_view')
            ->setEntity($entity)
            ->setStore($this->getOrder()->getStore());

        return $formProcessor;
    }

    /**
     * @return Order
     */
    protected function getOrder()
    {
        if (!$this->hasData('order_entity')) {
            $order = $this->coreRegistry->registry('current_order');

            if (!$order && $this->getParentBlock()) {
                $order = $this->getParentBlock()->getOrder();
            }

            $this->setData('order_entity', $order);
        }

        return $this->getData('order_entity');
    }
}
