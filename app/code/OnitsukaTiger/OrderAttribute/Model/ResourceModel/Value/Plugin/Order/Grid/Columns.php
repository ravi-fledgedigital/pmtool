<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Value\Plugin\Order\Grid;

/**
 * Class Columns
 */
class Columns
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ConfigProvider
     */
    protected $config;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\GridUiCaster
     */
    private $gridUiCaster;

    /**
     * @var \Magento\Framework\View\Element\UiComponentFactory
     */
    private $componentFactory;

    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory $attributeCollectionFactory,
        \OnitsukaTiger\OrderAttribute\Model\ConfigProvider $config,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\GridUiCaster $gridUiCaster,
        \Magento\Framework\View\Element\UiComponentFactory $componentFactory
    ) {
        $this->config = $config;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->gridUiCaster = $gridUiCaster;
        $this->componentFactory = $componentFactory;
    }

    /**
     * @param \Magento\Ui\Component\Listing\Columns $subject
     * @param \Closure                              $proceed
     */
    public function aroundPrepare(\Magento\Ui\Component\Listing\Columns $subject, \Closure $proceed)
    {
        if ($this->allowToAddAttributes($subject)) {
            $this->prepareOrderAttributes($subject);
        }

        $proceed();
    }

    /**
     * @param \Magento\Ui\Component\Listing\Columns $columnsComponent
     *
     * @return bool
     * @deprecated
     */
    private function allowedInlineEdit($columnsComponent)
    {
        return $columnsComponent->getName() == 'sales_order_columns';
    }

    /**
     * @param \Magento\Ui\Component\Listing\Columns $columnsComponent
     * @deprecated
     */
    private function addInlineEdit($columnsComponent)
    {
        $config = $columnsComponent->getData('config');
        /* some times xsi:type="boolean" recognizing as string, should be as boolean */
        /** @see app/code/OnitsukaTiger/OrderAttribute/view/adminhtml/ui_component/sales_order_grid.xml */
        $config['childDefaults']['fieldAction'] = [
            'provider' => 'sales_order_grid.sales_order_grid.sales_order_columns_editor',
            'target' => 'startEdit',
            'params' => [
                0 => '${ $.$data.rowIndex }',
                1 => true
            ]
        ];

        $columnsComponent->setData('config', $config);
    }

    /**
     * @param \Magento\Ui\Component\Listing\Columns $columnsComponent
     */
    protected function prepareOrderAttributes($columnsComponent)
    {
        $components = $columnsComponent->getChildComponents();
        foreach ($this->getAttributesList() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (!isset($components[$attributeCode])) {
                $column = $this->componentFactory->create(
                    $attributeCode,
                    'column',
                    $this->gridUiCaster->execute($attribute, $columnsComponent->getContext())
                );
                $column->prepare();

                $columnsComponent->addComponent($attributeCode, $column);
            }
        }
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute[]
     */
    protected function getAttributesList()
    {
        /** @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection $collection */
        $collection = $this->attributeCollectionFactory->create();
        $collection->setSortOrder()->addIsFilterableFilter();

        return $collection->getItems();
    }

    public function aroundPrepareDataSource(
        \Magento\Ui\Component\Listing\Columns $subject,
        \Closure $proceed,
        array $dataSource
    ) {
        if ($this->allowToAddAttributes($subject)) {
            $dataSource = $this->prepareDataForOrderAttributes($dataSource);
        }

        return $proceed($dataSource);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    protected function prepareDataForOrderAttributes(array $dataSource)
    {
        // TODO check if unnecessary
        foreach ($this->getAttributesList() as $attribute) {
            /**
             * @var \Magento\Eav\Model\Entity\Attribute $attribute
             */
            if (in_array($attribute->getFrontendInput(), ['checkboxes', 'multiselect'])) {
                $dataSource = $this->prepareDataForCheckboxes(
                    $dataSource,
                    $attribute->getAttributeCode()
                );
            }
        }

        return $dataSource;
    }

    /**
     * @param array $dataSource
     * @param       $attributeCode
     *
     * @return array
     */
    protected function prepareDataForCheckboxes(array $dataSource, $attributeCode)
    {
        $items = &$dataSource['data']['items'];
        foreach ($items as &$item) {
            if (array_key_exists($attributeCode, $item) && is_string($item[$attributeCode])) {
                $item[$attributeCode] = explode(',', $item[$attributeCode]);
            }
        }

        return $dataSource;
    }

    /**
     * Is can add order Attribute Columns to Component
     *
     * @param \Magento\Ui\Component\Listing\Columns $columnsComponent
     *
     * @return bool
     */
    public function allowToAddAttributes($columnsComponent)
    {
        $componentName = $columnsComponent->getName();
        $isOrder       = $componentName == 'sales_order_columns';
        $isInvoice     = $componentName == 'sales_order_invoice_columns' && $this->config->isShowInvoiceGrid();
        $isShipment    = $componentName == 'sales_order_shipment_columns' && $this->config->isShowShipmentGrid();

        return $isOrder || $isInvoice || $isShipment;
    }
}
