<?php

namespace OnitsukaTiger\NetSuite\Model\Queue;

use OnitsukaTiger\NetSuite\Api\Queue\CancelMessageInterface;

class CancelMessage implements CancelMessageInterface
{
    /**
     * @var string
     */
    protected $shipmentId;

    /**
     * @var string
     */
    protected $sourceCode;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $retry;


    public function setShipmentId($shipmentId){
        $this->shipmentId = $shipmentId;
    }

    /**
     * @return string
     */
    public function getShipmentId(): string
    {
        return $this->shipmentId;
    }

    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    public function getRetry()
    {
        return $this->retry;
    }

    public function setSourceCode($sourceCode)
    {
        $this->sourceCode = $sourceCode;
    }

    public function getSourceCode()
    {
        return $this->sourceCode;
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }
}
