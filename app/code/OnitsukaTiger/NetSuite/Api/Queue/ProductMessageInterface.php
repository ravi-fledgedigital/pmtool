<?php


namespace OnitsukaTiger\NetSuite\Api\Queue;


interface ProductMessageInterface
{
    const TOPIC_NAME = 'onitsukatiger.netsuite.product';

    /**
     * @param int $productId
     * @return void
     */
    public function setProductId($productId);

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @param $netsuiteWebTh
     * @return void
     */
    public function setNetsuiteWebTh($netsuiteWebTh);

    /**
     * @return bool
     */
    public function getNetsuiteWebTh();

    /**
     * @param $netsuiteWebMy
     * @return void
     */
    public function setNetsuiteWebMy($netsuiteWebMy);

    /**
     * @return bool
     */
    public function getNetsuiteWebMy();

    /**
     * @param $netsuiteWebSg
     * @return void
     */
    public function setNetsuiteWebSg($netsuiteWebSg);

    /**
     * @return bool
     */
    public function getNetsuiteWebSg();

    /**
     * @param $netsuiteWebVn
     * @return void
     */
    public function setNetsuiteWebVn($netsuiteWebVn);

    /**
     * @return bool
     */
    public function getNetsuiteWebVn();

    /**
     * @param int $retry
     * @return void
     */
    public function setRetry($retry);

    /**
     * @return string
     */
    public function getRetry();
}
