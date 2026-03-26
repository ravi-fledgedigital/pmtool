<?php


namespace OnitsukaTiger\NetSuite\Api\Queue;


interface CancelMessageInterface
{
    const TOPIC_NAME = 'onitsukatiger.netsuite.cancel';

    /**
     * @param $shipmentId
     * @return void
     */
    public function setShipmentId($shipmentId);

    /**
     * @return string
     */
    public function getShipmentId();

    /**
     * @param $sourceCode
     * @return void
     */
    public function setSourceCode($sourceCode);

    /**
     * @return string
     */
    public function getSourceCode();

    /**
     * @param $storeId
     * @return void
     */
    public function setStoreId($storeId);

    /**
     * @return string
     */
    public function getStoreId();

    /**
     * @param int $retry
     * @return void
     */
    public function setRetry($retry);

    /**
     * @return int
     */
    public function getRetry();
}
