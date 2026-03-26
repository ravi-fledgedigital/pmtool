<?php
/**
 * Copy Cowell Asia 2020
 */
namespace OnitsukaTiger\EmailToWareHouse\Plugin\Sales\Model\Order;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class PluginDisplayOrderDateFormat
 * @package OnitsukaTiger\EmailToWareHouse\Plugin\Sales\Model\Order
 */
class PluginDisplayOrderDateFormat{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * PluginDisplayOrderDateFormat constructor.
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @return string
     * @throws \Exception
     */
    public function afterGetCreatedAtFormatted(\Magento\Sales\Model\Order $subject)
    {
        $dateTime = new \DateTime($subject->getCreatedAt(), new \DateTimeZone($this->timezone->getConfigTimezone('store', $subject->getStore())));
        return $dateTime->format('d-M-Y');
    }
}
