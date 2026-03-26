<?php

namespace OnitsukaTiger\Rma\Block\Adminhtml\Order\Creditmemo\Create;

use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Registry;
use OnitsukaTiger\Rma\Helper\Data;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{

    /**
     * @var Data
     */
    protected $rmaHelperData;

    public function __construct(
        Context $context,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        Data $rmaHelperData,
        array $data = [])
    {
        $this->rmaHelperData = $rmaHelperData;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $data);
    }

    /**
     * Get update url
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        if (!$this->getRequest()->getParam('rma_request_id') ||
            !$this->rmaHelperData->getRmaToCreditMemoConfig($this->getOrder()->getStoreId())
        ) {
            return parent::getUpdateUrl();
        }

        return $this->getUrl(
            'sales/*/updateQty',
            [
                'order_id' => $this->getCreditmemo()->getOrderId(),
                'invoice_id' => $this->getRequest()->getParam('invoice_id', null),
                'rma_request_id' => $this->getRequest()->getParam('rma_request_id', null)
            ]
        );
    }
}
