<?php


namespace OnitsukaTiger\NetSuite\Model\Response;


class InventoryResponse extends \OnitsukaTiger\NetSuite\Model\Response\Response implements \OnitsukaTiger\NetSuite\Api\Response\InventoryResponseInterface
{

    /**
     * @var int
     */
    protected $updated;
    /**
     * @var int
     */
    protected $noLocation;
    /**
     * @var int
     */
    protected $noSku;
    /**
     * @var int
     */
    protected $sameQty;

    public function __construct(
        bool $success,
        int $updated,
        int $noLocation,
        int $noSku,
        int $sameQty
    )
    {
        $this->updated = $updated;
        $this->noLocation = $noLocation;
        $this->noSku = $noSku;
        $this->sameQty = $sameQty;

        parent::__construct($success);
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(int $updated)
    {
        $this->updated = $updated;
    }

    public function getNoLocation()
    {
        return $this->noLocation;
    }

    public function setNoLocation(int $noLocation)
    {
        $this->noLocation = $noLocation;
    }

    public function getNoSku()
    {
        return $this->noSku;
    }

    public function setNoSku(int $noSku)
    {
        $this->noSku = $noSku;
    }

    public function getSameQty()
    {
        return $this->sameQty;
    }

    public function setSameQty(int $sameQty)
    {
        $this->sameQty = $sameQty;
    }

    public function toString()
    {
        return json_encode([
            'success' => $this->getSuccess(),
            'updated' => $this->getUpdated(),
            'no_location' => $this->getNoLocation(),
            'no_sku' => $this->getNoSku(),
            'same_qty' => $this->getSameQty()
        ]);
    }
}
