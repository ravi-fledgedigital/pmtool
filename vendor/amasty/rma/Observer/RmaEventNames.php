<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer;

class RmaEventNames
{
    public const STATUS_CHANGED = 'amasty_rma_status_changed';
    public const STATUS_AUTOMATICALLY_CHANGED = 'amasty_rma_status_automatically_changed';

    //Customer Events
    public const NEW_CHAT_MESSAGE_BY_CUSTOMER = 'amasty_customer_rma_new_message';
    public const CHAT_MESSAGE_DELETED_BY_CUSTOMER = 'amasty_customer_rma_deleted_message';
    public const BEFORE_CREATE_RMA_BY_CUSTOMER = 'amasty_customer_rma_before_create';
    public const RMA_CREATED_BY_CUSTOMER = 'amasty_customer_rma_created';
    public const RMA_RATED = 'amasty_customer_rated_rma';
    public const TRACKING_NUMBER_ADDED_BY_CUSTOMER = 'amasty_customer_added_tracking_number_rma';
    public const TRACKING_NUMBER_DELETED_BY_CUSTOMER = 'amasty_customer_deleted_tracking_number_rma';
    public const RMA_CANCELED = 'amasty_customer_rma_canceled';
    //Admin Events
    public const NEW_CHAT_MESSAGE_BY_MANAGER = 'amasty_manager_rma_new_message';
    public const CHAT_MESSAGE_DELETED_BY_MANAGER = 'amasty_manager_rma_deleted_message';
    public const BEFORE_CREATE_RMA_BY_MANAGER = 'amasty_manager_rma_before_create';
    public const RMA_CREATED_BY_MANAGER = 'amasty_manager_rma_created';
    public const TRACKING_NUMBER_ADDED_BY_MANAGER = 'amasty_manager_added_tracking_number_rma';
    public const TRACKING_NUMBER_DELETED_BY_MANAGER = 'amasty_manager_deleted_tracking_number_rma';
    public const SHIPPING_LABEL_ADDED_BY_MANAGER = 'amasty_manager_added_shipping_label_rma';
    public const SHIPPING_LABEL_DELETED_BY_MANAGER = 'amasty_manager_deleted_shipping_label_rma';
    public const RMA_SAVED_BY_MANAGER = 'amasty_manager_rma_saved';
    //System Events
    public const STATUS_CHANGED_BY_SYSTEM = 'amasty_rma_system_status_changed';
    public const MANAGER_CHANGED_BY_SYSTEM = 'amasty_rma_system_manager_changed';
}
