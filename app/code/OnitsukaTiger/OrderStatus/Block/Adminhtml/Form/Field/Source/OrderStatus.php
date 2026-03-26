<?php
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Block\Adminhtml\Form\Field\Source;

use Magento\Sales\Model\Config\Source\Order\Status;

class OrderStatus extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [];
}
