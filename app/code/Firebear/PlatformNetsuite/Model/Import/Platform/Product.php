<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Platform;

use Firebear\PlatformNetsuite\Model\Import\Source\ProductFactory as SourceFactory;
use Firebear\PlatformNetsuite\Model\Import\Source\Product as Source;

/**
 * Netsuite product platform
 */
class Product extends AbstractPlatform
{
    /**
     * Initialize platform
     *
     * @param SourceFactory $sourceFactory
     */
    public function __construct(
        SourceFactory $sourceFactory
    ) {
        $this->_sourceFactory = $sourceFactory;
    }

    /**
     * Retrieve import source
     *
     * @param array $data
     * @return Source|\Magento\ImportExport\Model\Import\AbstractSource
     */
    public function getSource($data = [])
    {
        if (null == $this->_source) {
            $this->_source = $this->_sourceFactory->create(['data' => $data]);
            $this->_source->setPlatform($this);
        }
        return $this->_source;
    }
}
