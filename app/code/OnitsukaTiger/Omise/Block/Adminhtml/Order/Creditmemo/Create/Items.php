<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Omise\Block\Adminhtml\Order\Creditmemo\Create;

use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Data;

/**
 * Adminhtml credit memo items grid
 *
 * @api
 * @since 100.0.2
 */
class Items extends \OnitsukaTiger\Rma\Block\Adminhtml\Order\Creditmemo\Create\Items
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param Registry $registry
     * @param Data $salesData
     * @param \OnitsukaTiger\Rma\Helper\Data $rmaHelperData
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context, StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        \OnitsukaTiger\Rma\Helper\Data $rmaHelperData,
        array $data = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $rmaHelperData, $data);
    }


    /**
     * @return \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items|void
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('creditmemo_item_container'),'" . $this->getUpdateUrl() . "')";
        $this->addChild(
            'update_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Update Qty\'s'), 'class' => 'update-button', 'onclick' => $onclick]
        );

        if ($this->getCreditmemo()->canRefund()) {
            $classButtonOffline = 'save submit-button primary';
            if ($this->getCreditmemo()->getInvoice() && $this->getCreditmemo()->getInvoice()->getTransactionId() && $this->isEnabled()) {
                $classButtonOffline = 'save submit-button secondary';
                $this->addChild(
                    'submit_button',
                    \Magento\Backend\Block\Widget\Button::class,
                    [
                        'label' => __('Refund Online'),
                        'class' => 'save submit-button refund primary',
                        'onclick' => 'require([\'jquery\', \'Magento_Ui/js/modal/confirm\'], function(jQuery, confirm)
                        {
                            confirm({
                                content: \'Are you sure you want to refund online this order?\',
                                buttons: [{
                                    text: \'No\',
                                    class: \'action-secondary action-dismiss\',
                                    click: function (event) {
                                        this.closeModal(event);
                                    }
                                },{
                                    text: \'Yes\',
                                    class: \'action-primary action-accept\',
                                    click: function (event) {
                                        this.closeModal(event, true);
                                        disableElements(\'submit-button\');
                                        submitCreditMemo();
                                    }
                                }]
                            });
                        });'
                    ]
                );
            }
            $this->addChild(
                'submit_offline',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => $classButtonOffline,
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()'
                ]
            );
        } else {
            $this->addChild(
                'submit_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => 'save submit-button primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()'
                ]
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        if (!$this->scopeConfig->getValue('refund_online/general/omise_refund_online_enabled') && $paymentMethod == 'omise_cc') {
            return false;
        }
        return true;
    }
}
