<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

class View extends \Magento\Backend\App\Action
{

    /**
     * @var ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @param Action\Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param StoreShipping $storeShipping
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Action\Context $context,
        ShipmentLoader $shipmentLoader,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        StoreShipping $storeShipping,
        ShipmentRepository $shipmentRepository
    ) {
        $this->shipmentLoader = $shipmentLoader;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->storeShipping = $storeShipping;
        $this->shipmentRepository = $shipmentRepository;
        parent::__construct($context);
    }

    /**
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    protected function _isAllowed()
    {
        $shipment = $this->shipmentRepository->get($this->getRequest()->getParam('shipment_id'));
        $resource = 'OnitsukaTiger_NetSuiteStoreShipping::' . $shipment->getExtensionAttributes()->getSourceCode();
        return $this->_authorization->isAllowed($resource);
    }

    /**
     * @return Forward|ResponseInterface|ResultInterface|Page
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
        $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
        $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
        $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
        $shipment = $this->shipmentLoader->load();
        if ($shipment) {
            $resultPage = $this->resultPageFactory->create();
            $this->getRequest()->setParams(['come_from' => StoreShipping::STORE_SHIPPING_ROUTE]);
            $resultPage->getLayout()->getBlock('sales_shipment_view')
                ->updateBackButtonUrl(StoreShipping::STORE_SHIPPING_ROUTE);
            $resultPage->setActiveMenu('OnitsukaTiger_NetSuiteStoreShipping::manage');
            $resultPage->getConfig()->getTitle()->prepend(__('Shipments'));
            $resultPage->getConfig()->getTitle()->prepend("#" . $shipment->getIncrementId());
            return $resultPage;
        } else {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('noroute');
            return $resultForward;
        }
    }
}
