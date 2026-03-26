<?php

namespace OnitsukaTiger\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\Rma\Helper\Data;

class SyncButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * @var Data
     */
    protected Data $helperRma;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository,
        Data $helperRma
    ) {
        parent::__construct($context, $orderRepository, $requestRepository);
        $this->helperRma = $helperRma;
    }

    public function getButtonData()
    {
        $data = [];
        $requestRma = $this->requestRepository->getById($this->getRequestId());
        $enableButton = $this->helperRma->getIsShowSyncButton($requestRma->getStoreId());

        if (!$enableButton) {
            return $data;
        }
        return [
            'label' => __('Save & Sync'),
            'class' => 'amrma-sync',
            'id' => 'amrma-sync',
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'amrma_request_form.amrma_request_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    ['is_sync' => 1],
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }
}
