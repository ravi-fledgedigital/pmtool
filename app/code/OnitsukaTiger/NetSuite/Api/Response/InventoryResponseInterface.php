<?php


namespace OnitsukaTiger\NetSuite\Api\Response;


interface InventoryResponseInterface extends ResponseInterface
{

    /**
     * @return int
     */
    public function getUpdated();

    /**
     * @param int $updated
     * @return void
     */
    public function setUpdated(int $updated);

    /**
     * @return int
     */
    public function getNoLocation();

    /**
     * @param int $noLocation
     * @return void
     */
    public function setNoLocation(int $noLocation);

    /**
     * @return int
     */
    public function getNoSku();

    /**
     * @param int $noSku
     * @return void
     */
    public function setNoSku(int $noSku);

    /**
     * @return int
     */
    public function getSameQty();

    /**
     * @param int $sameQty
     * @return void
     */
    public function setSameQty(int $sameQty);
}
