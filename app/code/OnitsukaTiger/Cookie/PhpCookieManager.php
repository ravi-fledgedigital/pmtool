<?php

namespace OnitsukaTiger\Cookie;

class PhpCookieManager extends \Magento\Framework\Stdlib\Cookie\PhpCookieManager
{
    /**#@+
     * Constants for Cookie manager.
     * RFC 2109 - Page 15
     * http://www.ietf.org/rfc/rfc6265.txt
     */
    const MAX_NUM_COOKIES = 200;
}
