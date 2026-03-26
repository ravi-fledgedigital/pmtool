<?php

namespace OnitsukaTiger\OrderStatus\Model\Response;

class KerryResponse implements \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseInterface
{
    protected $res;

    public function __construct(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface $res)
    {
        $this->res = $res;
    }

    /**
     * @param \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface $res
     * @return void
     */
    public function setRes(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface $res)
    {
        $this->res = $res;
    }

    /**
     * @return \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface
     */
    public function getRes()
    {
        return $this->res;
    }

    /**
     * @return false|string
     */
    public function toString()
    {
        return json_encode($this);
    }
}
