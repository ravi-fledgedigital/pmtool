<?php

namespace OnitsukaTiger\PreOrders\Helper\Source;

interface PreOrderNoteScope
{
    const CATEGORY_PAGE_SCOPE = 1;
    const PRODUCT_PAGE_SCOPE = 2;
    const MINI_CART_SCOPE = 3;
    const CART_SCOPE = 4;
    const CHECKOUT_SCOPE = 5;
    const SEARCH_RESULT_SCOPE = 6;
    const WISH_LIST_SCOPE = 7;
    const COMPARE_LIST_SCOPE = 8;
}
