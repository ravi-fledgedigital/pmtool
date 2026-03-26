<?php
namespace OnitsukaTigerIndo\Biteship\Ui\Component\Listing\Column;

use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Get BiteshipId
 */
class BiteshipOrderId extends Column
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * Constructs a new instance.
     *
     * @param \Magento\Sales\Model\Order $order The order
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context The context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * The user interface component factory
     * @param array $components The components
     * @param array $data The data
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->order = $order;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Getting datasource
     *
     * @param      array  $dataSource  The data source
     *
     * @return     array  ( description_of_the_return_value )
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $orders = $this->order->load($item['entity_id'], "entity_id");
                $bitehshipOrderId = $orders->getData("biteship_order_id");
                $item['biteship_order_id'] = $bitehshipOrderId;
            }
        }
        return $dataSource;
    }
}
