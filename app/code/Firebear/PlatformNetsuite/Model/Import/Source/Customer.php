<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

/**
 * Netsuite customer source
 */
class Customer extends AbstractSource
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
     * @param Gateway\Customer $gateway
     * @param \Magento\Framework\App\RequestInterface $request
     * @param null $data
     * @throws \Exception
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Customer $gateway,
        \Magento\Framework\App\RequestInterface $request,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->request = $request;
        $this->parseEntities($data);
        $this->_colNames = array_keys($this->entities[0] ?? []);
    }
}
