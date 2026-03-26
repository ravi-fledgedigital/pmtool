<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\Import\Context;
use Firebear\ImportExportMsi\Model\Import\StockSourceQty;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Indexer\Model\Indexer\StateFactory;
use Magento\InventoryImportExport\Model\Import\Serializer\Json as JsonHelper;
use Magento\InventoryImportExport\Model\Import\Validator\ValidatorInterface;

/**
 * Class LocationQty
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class LocationQty extends StockSourceQty
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * LocationQty constructor.
     * @param Context $context
     * @param JsonHelper $jsonHelper
     * @param ValidatorInterface $validator
     * @param StateFactory $stateFactory
     * @param ProductFactory $productFactory
     * @param Data $helper
     * @param array $commands
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        ValidatorInterface $validator,
        StateFactory $stateFactory,
        ProductFactory $productFactory,
        Data $helper,
        array $commands = []
    ) {
        parent::__construct(
            $context,
            $jsonHelper,
            $validator,
            $stateFactory,
            $productFactory,
            $commands
        );
        $this->_helper = $helper;
    }
}
