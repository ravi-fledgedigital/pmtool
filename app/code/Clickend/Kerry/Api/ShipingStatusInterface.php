<?php
namespace Clickend\Kerry\Api;
//namespace Clickend\Kerry\Api\Data\datashipping;

interface ShipingStatusInterface
{
    /**
     * @api
     * @return \OnitsukaTiger\OrderStatus\Api\Response\KerryResponseInterface
     */
    public function shipingstatusdata();
}