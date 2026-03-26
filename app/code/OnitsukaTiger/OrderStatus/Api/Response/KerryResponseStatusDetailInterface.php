<?php

namespace OnitsukaTiger\OrderStatus\Api\Response;

interface KerryResponseStatusDetailInterface
{
    /**
     * @param string $statusCode
     * @return void
     */
    public function setStatusCode(string $statusCode);

    /**
     * @return string
     */
    public function getStatusCode();

    /**
     * @param string $statusDesc
     * @return void
     */
    public function setStatusDesc(string $statusDesc);

    /**
     * @return string
     */
    public function getStatusDesc();
}
