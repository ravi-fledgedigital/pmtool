<?php

namespace OnitsukaTiger\NetSuite\Model\Queue;

class ProductMessage implements \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface
{
    protected $productId;
    protected $netsuiteWebTh;
    protected $netsuiteWebMy;
    protected $netsuiteWebSg;
    protected $netsuiteWebVn;
    protected $retry;

    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setNetsuiteWebTh($netsuiteWebTh) {
        $this->netsuiteWebTh = $netsuiteWebTh;
    }

    /**
     * @return bool
     */
    public function getNetsuiteWebTh()
    {
        return $this->netsuiteWebTh;
    }

    public function setNetsuiteWebMy($netsuiteWebMy) {
        $this->netsuiteWebMy = $netsuiteWebMy;
    }

    /**
     * @return bool
     */
    public function getNetsuiteWebMy()
    {
        return $this->netsuiteWebMy;
    }

    public function setNetsuiteWebSg($netsuiteWebSg) {
        $this->netsuiteWebSg = $netsuiteWebSg;
    }

    /**
     * @return bool
     */
    public function getNetsuiteWebSg()
    {
        return $this->netsuiteWebSg;
    }

    public function setNetsuiteWebVn($netsuiteWebVn) {
        $this->netsuiteWebVn = $netsuiteWebVn;
    }

    /**
     * @return bool
     */
    public function getNetsuiteWebVn()
    {
        return $this->netsuiteWebVn;
    }

    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    public function getRetry()
    {
        return $this->retry;
    }
}
