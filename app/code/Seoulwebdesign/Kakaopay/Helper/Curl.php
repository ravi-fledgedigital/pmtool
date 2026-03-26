<?php

namespace Seoulwebdesign\Kakaopay\Helper;

class Curl extends \Magento\Framework\HTTP\Client\Curl
{
    public function delete($uri)
    {
        $this->makeRequest("DELETE", $uri);
    }

    public function patch($uri, $params)
    {
        $this->makeRequest("PATCH", $uri, $params);
    }
}
