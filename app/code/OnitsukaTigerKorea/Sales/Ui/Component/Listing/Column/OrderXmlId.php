<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Sales\Ui\Component\Listing\Column;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Sales\Model\OrderRepository;

class OrderXmlId extends Column
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepository $orderRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepository $orderRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['totalRecords'])
            && $dataSource['data']['totalRecords'] > 0
        ) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row['order_xml_id'] = $this->getOrderXmlId((int)$row['order_id']);
            }
        }
        unset($row);

        return $dataSource;
    }

    /**
     * @param int $orderId
     * @return mixed
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getOrderXmlId(int $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getData('order_xml_id');
    }
}
