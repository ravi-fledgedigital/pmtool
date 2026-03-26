<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

class EventType implements OptionSourceInterface
{
    public const RMA_CREATED = 0;
    public const TRACKING_NUMBER_ADDED = 1;
    public const TRACKING_NUMBER_DELETED = 2;
    public const NEW_MESSAGE = 3;
    public const DELETED_MESSAGE = 4;
    public const CUSTOMER_CLOSED_RMA = 5;
    public const STATUS_AUTOMATICALLY_CHANGED = 6;
    public const MANAGER_SAVED_RMA = 7;
    public const MANAGER_ADDED_SHIPPING_LABEL = 8;
    public const MANAGER_DELETED_SHIPPING_LABEL = 9;
    public const SYSTEM_CHANGED_STATUS = 10;
    public const SYSTEM_CHANGED_MANAGER = 11;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::RMA_CREATED => __('Rma Created'),
            self::TRACKING_NUMBER_ADDED => __('Tracking Number Added'),
            self::TRACKING_NUMBER_DELETED => __('Tracking Number Deleted'),
            self::NEW_MESSAGE => __('New Message'),
            self::DELETED_MESSAGE => __('Deleted Message'),
            self::CUSTOMER_CLOSED_RMA => __('Customer Closed Rma'),
            self::STATUS_AUTOMATICALLY_CHANGED => __('Status Automatically Changed'),
            self::MANAGER_SAVED_RMA => __('Manager Saved Rma'),
            self::MANAGER_ADDED_SHIPPING_LABEL => __('Manager Added Shipping Label'),
            self::MANAGER_DELETED_SHIPPING_LABEL => __('Manager Deleted Shipping Label'),
            self::SYSTEM_CHANGED_STATUS => __('System Changed Status'),
            self::SYSTEM_CHANGED_MANAGER => __('System Changed Manager')
        ];
    }
}
