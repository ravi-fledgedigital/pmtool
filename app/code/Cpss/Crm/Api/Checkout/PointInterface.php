<?php

namespace Cpss\Crm\Api\Checkout;

/**
 * Pointbox Point Interface
 * @api
 */
interface PointInterface
{
    /**
     * @param mixed $point
     * @return mixed
     */
    public function set($point);

    /**
     * @param mixed $point
     * @return mixed
     */
    public function remove($point);

    /**
     * @return mixed
     */
    public function getEarnedPoints();
}
