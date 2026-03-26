<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Plugin\OrderView;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Backend\Model\Session;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class CreateRmaButton
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        ConfigProvider $configProvider,
        Session $session
    ) {
        $this->configProvider = $configProvider;
        $this->session = $session;
    }

    /**
     * @param OrderView $subject
     */
    public function beforeSetLayout(OrderView $subject)
    {
        if ($this->configProvider->isEnabled()) {
            $backToRmaFlow = $this->session->getBackToRmaFlow() ?? [];
            $flowOrder = $backToRmaFlow[$subject->getOrder()->getId()] ?? [];
            $requestId = $flowOrder['request_id'] ?? 0;

            if ($requestId && $flowOrder['complete'] ?? false) {

                $subject->addButton(
                    'amrma_back_to_rma',
                    [
                        'label' => __('Back to Return'),
                        'class' => 'amrma-back-to-rma',
                        'id' => 'amrma-back-to-rma',
                        'onclick' => $this->getBackToRmaUrl($subject, (int)$requestId)
                    ],
                    -1,
                    -1
                );
            }

            if ($statuses = $this->configProvider->getAllowedOrderStatuses($subject->getOrder()->getStoreId())) {
                if (!in_array($subject->getOrder()->getStatus(), $statuses)) {
                    return;
                }
            }

            $subject->addButton(
                'amrma_create',
                [
                    'label' => __('Create Return'),
                    'class' => 'amrma-create-return-button',
                    'id' => 'amrma-create-return-button',
                    'onclick' => "setLocation('" . $subject->getUrl('amrma/request/create') . "')"
                ]
            );
        }
    }

    private function getBackToRmaUrl(OrderView $subject, int $requestId): string
    {
        return sprintf(
            'setLocation("%s")',
            $subject->getUrl('amrma/request/view', ['request_id' => $requestId])
        );
    }
}
