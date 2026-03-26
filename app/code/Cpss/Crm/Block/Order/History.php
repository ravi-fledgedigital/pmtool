<?php
namespace Cpss\Crm\Block\Order;

use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class History extends \Magento\Sales\Block\Order\History
{
    /**
     * @var string
     */
    protected $_template = 'Cpss_Crm::order/history.phtml';

    public function getStore()
    {
        return 'EC';
    }

    public function getPaymentMethod()
    {
        return __("Cash/credit card payment");
    }
}
