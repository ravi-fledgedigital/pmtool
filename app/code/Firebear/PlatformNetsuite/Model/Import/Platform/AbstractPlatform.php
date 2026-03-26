<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Platform;

use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Model\Source\Platform\PlatformGatewayInterface;

/**
 * Netsuite abstract platform
 */
class AbstractPlatform implements PlatformInterface, PlatformGatewayInterface
{
    /**
     * Source
     *
     * @var \Firebear\PlatformNetsuite\Model\Import\Platform\Source\Product
     */
    protected $_source;

    /**
     * Source factory
     *
     * @var \Firebear\PlatformNetsuite\Model\Import\Platform\Source\AbstractSource
     */
    protected $_sourceFactory;

    /**
     * Prepare input data
     *
     * @param mixed $data
     * @return mixed
     */
    public function prepareData($data)
    {
        return $data;
    }

    /**
     * Delete columns before replace values
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function deleteColumns($data)
    {
        return $data;
    }

    /**
     * Prepare columns before replace columns
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function prepareColumns($data)
    {
        return $data;
    }

    /**
     * Post prepare columns after replace columns
     *
     * @param $data
     * @param $maps
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function afterColumns($data, $maps)
    {
        return $data;
    }

    /**
     * Prepare row
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Model\Source\Platform\AbstractPlatform
     */
    public function prepareRow($data)
    {
        return $data;
    }

    /**
     * Retrieve field name pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return '/^[a-zA-Z][a-zA-Z0-9_\:]*$/';
    }

    /**
     * Check if platform is gateway
     *
     * @return bool
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * Retrieve import source
     *
     * @return \Magento\ImportExport\Model\Import\AbstractSource
     */
    public function getSource()
    {
        if (null == $this->_source) {
            $this->_source = $this->_sourceFactory->create();
            $this->_source->setPlatform($this);
        }
        return $this->_source;
    }
}
