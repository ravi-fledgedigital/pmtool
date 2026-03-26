<?php

namespace WeltPixel\GA4\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface;

/**
 * Class OrderSentToMeasurementProtocol
 * @package WeltPixel\GA4\Ui\Component\Listing\Column
 */
class OrderSentToMeasurementProtocol extends Column
{
    /**
     * @var PurchaseInterface
     */
    private $purchaseInterface;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PurchaseInterface $purchaseInterface
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PurchaseInterface $purchaseInterface,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->purchaseInterface = $purchaseInterface;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Get data
     *
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        $content = __('No');
        $color = 'auto';

        $entityId = array_key_exists('entity_id', $item) ? $item['entity_id'] : null;
        if ($entityId) {
                $isOrderPushed = $this->purchaseInterface->isOrderPushed($entityId);
                switch ($isOrderPushed) {
                    case '2':
                        $color = 'green';
                        $content = __('Yes');
                    break;
                    case '1':
                        $color = 'red';
                        $content = __('No');
                        break;
                    default:
                        $color = 'grey';
                        $content = __('-');
                        break;
                }
        }

        return '<span style="text-align: center; color:' . $color . '">' . $content . '</span>';
    }
}
