<?php
namespace Cpss\Crm\Api\Btoc;

interface OrderHistoryInterface
{
    /**
     * Get member order history
     * @api
     * @return json
     */
    public function getMemberOrderHistory();
}