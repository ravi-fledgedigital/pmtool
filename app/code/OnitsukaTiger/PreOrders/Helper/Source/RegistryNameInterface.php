<?php

namespace OnitsukaTiger\PreOrders\Helper\Source;

interface RegistryNameInterface
{
    const CURRENT_CART_TYPE = 'pre_order_current_cart_type';
    const CURRENT_PRODUCT_IDS = 'pre_order_current_product_ids';
    const CURRENT_SEARCH_PRODUCT_IDS = 'pre_order_current_search_product_ids';
    const CURRENT_PRODUCT_LIST_PRODUCT_ID = 'pre_order_current_product_list_product_id';
    const CURRENT_PRODUCT = 'pre_order_current_product';
    const CURRENT_STOCK_ITEM = 'pre_order_current_stock_item';
    const CURRENT_MINICART_PRODUCT_ID = 'pre_order_current_minicart_product_id';
    const CURRENT_QUOTE_ITEM = 'pre_order_current_quote_item';
    const SAVED_PRE_ORDER_PRODUCT = 'pre_order_saved_pre_order_product';
}
