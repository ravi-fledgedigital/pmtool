<?php


namespace OnitsukaTiger\NetSuite\Api\Response;


interface ProductInternalIdResponseInterface extends ResponseInterface
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
    public function getNoSku();

    /**
     * @param int $noSku
     * @return void
     */
    public function setNoSku(int $noSku);

    /**
     * @return int
     */
    public function getSameId();

    /**
     * @param int $sameId
     * @return void
     */
    public function setSameId(int $sameId);
}
