<?php

namespace OnitsukaTiger\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use \OnitsukaTiger\Rma\Helper\DataCancelReturn as HelperRma;
use Amasty\Rma\Model\OptionSource\State;

class CancelButton extends GenericButton implements ButtonProviderInterface
{


    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    protected $helperRma;

    /**
     * @var Repository
     */
    protected $requestRepository;
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository,
        HelperRma $helperRma
    ) {
        parent::__construct($context,$orderRepository,$requestRepository);
        $this->request = $context->getRequest();
        $this->orderRepository = $orderRepository;
        $this->requestRepository = $requestRepository;
        $this->helperRma = $helperRma;

    }

    public function getButtonData()
    {
        $data = [];
        $requestRma = $this->requestRepository->getById($this->getRequestId());
        $order = $this->getOrderById($requestRma->getOrderId());
        $enableButton = $this->helperRma->getIsShowAdminCanceledStatusConfig($order->getStoreId());

        if ( !$enableButton || !$this->helperRma->validCancelStatus($order,$requestRma) ){
            return $data;
        }

        $alertMessage = __('Are you sure you want to do this?');
        $onClick = sprintf('confirmSetLocation("%s", "%s")', $alertMessage, $this->getCancelUrl());
        return [
            'label' => __('Cancel Return'),
            'class' => 'amrma-cancel-button',
            'id' => 'amrma-cancel-button',
            'on_click' => $onClick,
        ];
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl(
            'amrma_cancel/cancel/index/',
            [RegistryConstants::REQUEST_ID => $this->getRequestId()]);
    }

    /**
     * @return int
     */
    public function getRequestId()
    {
        return (int)$this->request->getParam(RegistryConstants::REQUEST_ID);
    }

}
