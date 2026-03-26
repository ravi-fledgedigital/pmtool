<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_Smtp
 */


namespace Aitoc\Smtp\Model\Resolver;

/**
 * Class From
 * @package Aitoc\Smtp\Model\Resolver
 */
class From
{
    /**
     * @var null
     */
    protected $from = null;

    /**
     * @return null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->from = null;

        return $this;
    }
}
