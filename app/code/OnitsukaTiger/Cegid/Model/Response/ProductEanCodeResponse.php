<?php

namespace OnitsukaTiger\Cegid\Model\Response;

use OnitsukaTiger\Cegid\Model\Response\Response;
use OnitsukaTiger\Cegid\Api\Response\ProductEanCodeResponseInterface;

class ProductEanCodeResponse extends Response implements ProductEanCodeResponseInterface
{

    /**
     * @var int
     */
    protected int $updated;

    /**
     * @var int
     */
    protected int $noSku;

    /**
     * @var int
     */
    protected int $sameEanCode;

    /**
     * @param bool $success
     * @param int $updated
     * @param int $noSku
     * @param int $sameEanCode
     */
    public function __construct(
        bool $success,
        int $updated,
        int $noSku,
        int $sameEanCode
    ) {
        $this->updated = $updated;
        $this->noSku = $noSku;
        $this->sameEanCode = $sameEanCode;
        parent::__construct($success);
    }

    /**
     * Get Updated
     *
     * @return int
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Set Updated
     *
     * @param int $updated
     * @return void
     */
    public function setUpdated(int $updated): void
    {
        $this->updated = $updated;
    }

    /**
     * Get No Sku
     *
     * @return int
     */
    public function getNoSku(): int
    {
        return $this->noSku;
    }

    /**
     * Set No Sku
     *
     * @param int $noSku
     * @return void
     */
    public function setNoSku(int $noSku): void
    {
        $this->noSku = $noSku;
    }

    /**
     * Get Same Ean Code
     *
     * @return int
     */
    public function getSameEanCode(): int
    {
        return $this->sameEanCode;
    }

    /**
     * Set Same Ean Code
     *
     * @param int $sameEanCode
     * @return void
     */
    public function setSameEanCode(int $sameEanCode): void
    {
        $this->sameEanCode = $sameEanCode;
    }

    /**
     * To String
     *
     * @return string
     */
    public function toString(): string
    {
        return json_encode([
            'success' => $this->getSuccess(),
            'updated' => $this->getUpdated(),
            'no_sku' => $this->getNoSku(),
            'same_code' => $this->getSameEanCode()
        ]);
    }

}
