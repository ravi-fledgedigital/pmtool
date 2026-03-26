<?php


namespace OnitsukaTiger\NetSuite\Model\Response;


class ProductInternalIdResponse extends \OnitsukaTiger\NetSuite\Model\Response\Response implements \OnitsukaTiger\NetSuite\Api\Response\ProductInternalIdResponseInterface
{

    /**
     * @var int
     */
    protected $updated;
    /**
     * @var int
     */
    protected $noSku;
    /**
     * @var int
     */
    protected $sameId;

    public function __construct(
        bool $success,
        int $updated,
        int $noSku,
        int $sameId
    )
    {
        $this->updated = $updated;
        $this->noSku = $noSku;
        $this->sameId = $sameId;

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

    public function getNoSku()
    {
        return $this->noSku;
    }

    public function setNoSku(int $noSku)
    {
        $this->noSku = $noSku;
    }

    public function getSameId()
    {
        return $this->sameId;
    }

    public function setSameId(int $sameId)
    {
        $this->sameId = $sameId;
    }

    public function toString()
    {
        return json_encode([
            'success' => $this->getSuccess(),
            'updated' => $this->getUpdated(),
            'no_sku' => $this->getNoSku(),
            'same_id' => $this->getSameId()
        ]);
    }
}
