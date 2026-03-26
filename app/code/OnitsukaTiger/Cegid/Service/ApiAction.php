<?php

namespace OnitsukaTiger\Cegid\Service;

class ApiAction
{
    /**
     * @var null
     */
    protected $shipmentStatusOld = null;

    /**
     * SetShipmentStatusOld
     *
     * @param string $params
     * @return mixed
     */
    public function setShipmentStatusOld($params)
    {
        return $this->shipmentStatusOld = $params;
    }

    /**
     * GetShipmentStatusOld
     *
     * @return mixed|null
     */
    public function getShipmentStatusOld()
    {
        return $this->shipmentStatusOld;
    }
}
