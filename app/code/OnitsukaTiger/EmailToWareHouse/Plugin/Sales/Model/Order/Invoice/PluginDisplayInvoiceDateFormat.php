<?php
/**
 * Copy Cowell Asia 2020
 */
namespace OnitsukaTiger\EmailToWareHouse\Plugin\Sales\Model\Order\Invoice;

/**
 * Class PluginDisplayInvoiceDateFormat
 * @package OnitsukaTiger\EmailToWareHouse\Plugin\Sales\Model\Order\Invoice
 */
class PluginDisplayInvoiceDateFormat
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $subject
     * @param $result
     * @return false|string
     */
    public function afterGetCreatedAt(\Magento\Sales\Model\Order\Invoice $subject, $result)
    {
        if (!empty($result)) {
            $resultValue = $result;
            $invoiceDateFormat = date("d-M-Y", strtotime($resultValue));
            return $invoiceDateFormat;
        }
        return $result;
    }
}

