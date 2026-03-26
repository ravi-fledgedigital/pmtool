<?php

namespace OnitsukaTiger\OrderStatus\Api\Response;

interface KerryResponseStatusInterface
{
    /**
     * @param \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface $status
     * @return void
     */
    public function setStatus(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface $status);

    /**
     * @return \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface
     */
    public function getStatus();
}
