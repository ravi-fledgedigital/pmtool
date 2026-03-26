<?php

namespace OnitsukaTiger\OrderStatus\Api\Response;

interface KerryResponseInterface
{
    /**
     * @param \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface $res
     * @return void
     */
    public function setRes(\OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface $res);

    /**
     * @return \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusInterface
     */
    public function getRes();
}
