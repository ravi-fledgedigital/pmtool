<?php

namespace OnitsukaTiger\OrderStatus\Model\Response;

class KerryResponseStatus implements \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface
{
    protected $status;
    public function __construct(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface $status)
    {
        $this->status = $status;
    }

    /**
     * @param \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface $status
     * @return void
     */
    public function setStatus(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface $status)
    {
        $this->status = $status;
    }

    /**
     * @return \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface
     */
    public function getStatus()
    {
        return $this->status;
    }
}
