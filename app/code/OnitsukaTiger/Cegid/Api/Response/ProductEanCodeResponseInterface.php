<?php
namespace OnitsukaTiger\Cegid\Api\Response;

interface ProductEanCodeResponseInterface extends ResponseInterface
{

    /**
     * Get Updated
     *
     * @return int
     */
    public function getUpdated(): int;

    /**
     * Set Updated
     *
     * @param int $updated
     * @return void
     */
    public function setUpdated(int $updated): void;

    /**
     * Get No Sku
     *
     * @return int
     */
    public function getNoSku(): int;

    /**
     * Set No Sku
     *
     * @param int $noSku
     * @return void
     */
    public function setNoSku(int $noSku): void;

    /**
     * Get Same Ean Code
     *
     * @return int
     */
    public function getSameEanCode(): int;

    /**
     * Set Same Ean Code
     *
     * @param int $sameEanCode
     * @return void
     */
    public function setSameEanCode(int $sameEanCode): void;
}
