<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Sales\Model;

use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;

class OrderXmlId
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    /**
     * OrderXmlId constructor.
     * @param Logger $logger
     * @param OrderRepository $orderRepository
     * @param ExportXml $exportXml
     */
    public function __construct(
        Logger $logger,
        OrderRepository $orderRepository,
        ExportXml $exportXml
    )
    {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->exportXml = $exportXml;
    }

    /**
     * @param $orderId
     * @param $xmlId
     * @param $prefix
     */
    public function updateOrderXmlId($orderId, $xmlId, $prefix)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $currentXmlId = $order->getData('order_xml_id') ? $order->getData('order_xml_id') . ', ' : $order->getData('order_xml_id');
            $order->setData('order_xml_id', $currentXmlId . $this->exportXml->addPrefix($xmlId, $prefix));
            $this->orderRepository->save($order);
        } catch (\Exception $exception) {
            $this->logger->critical('Update order Xml id false:' . $exception->getMessage());
        }
    }

    /**
     * @param $request
     * @return bool
     */
    public function isNotExistReturnXmlId($request)
    {
        try {
            $order = $this->orderRepository->get($request->getOrderId());
            if ($order->getData('order_xml_id')) {
                $returnXmlId = $this->exportXml->addPrefix($request->getRequestId(), ExportXml::PREFIX_RETURN);
                return !in_array($returnXmlId, explode(',', $order->getData('order_xml_id')));
            }
            return true;
        } catch (\Exception $exception) {
            $this->logger->critical(sprintf('Update order %s Xml id false: Error: %s', $order->getIncrementId(), $exception->getMessage()));
            return false;
        }
    }
}
