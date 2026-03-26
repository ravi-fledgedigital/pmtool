<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model\Http\Adapter;

class Curl extends \Magento\Framework\HTTP\Adapter\Curl
{
    public function write($method, $url, $http_ver = '1.1', $headers = [], $body = '')
    {
        parent::write($method, $url, $http_ver, $headers, $body);
        if ($method == \Zend_Http_Client::DELETE) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, \Zend_Http_Client::DELETE);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        }
    }
}
