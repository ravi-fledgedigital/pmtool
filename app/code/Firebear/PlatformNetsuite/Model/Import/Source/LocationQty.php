<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Netsuite product source
 */
class LocationQty extends AbstractSource
{
    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\LocationQty
     */
    protected $gateway;

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * LocationQty constructor.
     * @param Gateway\Location $gateway
     * @param RequestInterface $request
     * @param null $data
     * @throws Exception
     */
    public function __construct(
        Gateway\LocationQty $gateway,
        RequestInterface $request,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->request = $request;
        $this->parseEntities($data);
    }
}
