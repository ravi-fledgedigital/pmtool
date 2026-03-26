<?php

namespace OnitsukaTiger\OrderStatus\Model\Response;

class KerryResponseStatusDetail implements \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseStatusDetailInterface
{
    /**
     * @var string
     */
    private $statusCode;

    /**
     * @var string
     */
    private $statusDesc;

    /**
     * @param string $statusCode
     * @param string $statusDesc
     */
    public function __construct(string $statusCode, string $statusDesc)
    {
        $this->statusCode = $statusCode;
        $this->statusDesc = $statusDesc;
    }

    /**
     * @param string $statusCode
     * @return void
     */
    public function setStatusCode(string $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string $statusDesc
     * @return void
     */
    public function setStatusDesc(string $statusDesc)
    {
        $this->statusDesc = $statusDesc;
    }

    /**
     * @return string
     */
    public function getStatusDesc()
    {
        return $this->statusDesc;
    }
}
