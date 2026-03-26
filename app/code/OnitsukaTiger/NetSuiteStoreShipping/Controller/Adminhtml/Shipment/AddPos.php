<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\StoreShipping\Logger;

/**
 * Add new pos receipt number to shipment controller.
 */
class AddPos extends Action implements HttpPostActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Logger
     */
    protected $logger;


    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Action\Context $context
     * @param ShipmentRepositoryInterface|null $shipmentRepository
     * @param SerializerInterface|null $serializer
     * @param Registry $registry
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface                  $orderRepository,
        Action\Context                            $context,
        ShipmentRepositoryInterface               $shipmentRepository = null,
        SerializerInterface                       $serializer = null,
        Registry                                  $registry,
        Logger                                    $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        parent::__construct($context);

        $this->shipmentRepository = $shipmentRepository ?: ObjectManager::getInstance()
            ->get(ShipmentRepositoryInterface::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(SerializerInterface::class);
        $this->registry = $registry;
    }

    /**
     * Add new tracking number action.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $number = $this->getRequest()->getPost('number');

            if (empty($number)) {
                throw new LocalizedException(__('Please enter a pos receipt number.'));
            }

            $shipmentId = (int) $this->getRequest()->getParam('shipment_id');
            $shipment = $this->shipmentRepository->get($shipmentId);
            if ($shipment) {
                $this->registry->register('current_shipment', $shipment);
                $shipment->getExtensionAttributes()->setPosReceiptNumber($number);
                $this->shipmentRepository->save($shipment);

                $shipment->getOrder()->addCommentToStatusHistory(sprintf('Staff have set POS receipt number: %s', $number));
                $this->orderRepository->save($shipment->getOrder());

                $this->_view->loadLayout();
                $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipments'));
                $response = $this->_view->getLayout()->getBlock('shipment_pos')->toHtml();
            } else {
                $response = [
                    'error' => true,
                    'message' => __('We can\'t initialize shipment for adding pos receipt number.'),
                ];
            }
        } catch (LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
            $this->logger->error(sprintf('SPS: Error add POS shipment [%s]. Message: [%s]', $shipmentId, $e->getMessage()));
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('Cannot add pos receipt number.')];
            $this->logger->error(sprintf('Cannot add pos receipt number. [%s]. Message: [%s]', $shipmentId, $e->getMessage()));
        }

        if (\is_array($response)) {
            $response = $this->serializer->serialize($response);

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData($response);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($response);
    }
}
