<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Magento\Framework\Exception\LocalizedException;

/**
 * Netsuite product source
 */
class Product extends AbstractSource
{
    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\Product
     */
    protected $gateway;

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Product constructor.
     * @param Gateway\Product $gateway
     * @param \Magento\Framework\App\RequestInterface $request
     * @param null $data
     * @throws \Exception
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Product $gateway,
        \Magento\Framework\App\RequestInterface $request,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->request = $request;
        $this->parseEntities($data);
        $this->_colNames = array_keys($this->entities[0] ?? []);
    }
}
