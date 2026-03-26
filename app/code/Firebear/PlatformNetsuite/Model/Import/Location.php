<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as DataSourceModel;
use Firebear\ImportExportMsi\Model\Import\StockSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Validation\ValidationException;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\InventoryImportExport\Model\Import\Serializer\Json as JsonHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Class Location
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class Location extends StockSource
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * Entity columns
     *
     * @var string[]
     */
    protected $_importFields = [
        SourceInterface::SOURCE_CODE,
        SourceInterface::NAME,
        SourceInterface::CONTACT_NAME,
        SourceInterface::EMAIL,
        SourceInterface::ENABLED,
        SourceInterface::DESCRIPTION,
        SourceInterface::LATITUDE,
        SourceInterface::LONGITUDE,
        SourceInterface::COUNTRY_ID,
        SourceInterface::REGION_ID,
        SourceInterface::REGION,
        SourceInterface::CITY,
        SourceInterface::STREET,
        SourceInterface::POSTCODE,
        SourceInterface::PHONE,
        SourceInterface::FAX,
        SourceInterface::USE_DEFAULT_CARRIER_CONFIG,
        'netsuite_internal_id'
    ];

    /**
     * Location constructor.
     * @param StringUtils $string
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param ResourceHelper $resourceHelper
     * @param ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ConsoleOutput $output
     * @param LoggerInterface $logger
     * @param ImportExportData $importExportData
     * @param JsonHelper $jsonHelper
     * @param DataSourceModel $dataSourceModel
     * @param Data $helper
     * @param SourceInterface $source
     * @param array $data
     */
    public function __construct(
        StringUtils $string,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        ResourceHelper $resourceHelper,
        ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ConsoleOutput $output,
        LoggerInterface $logger,
        ImportExportData $importExportData,
        JsonHelper $jsonHelper,
        DataSourceModel $dataSourceModel,
        Data $helper,
        SourceInterface $source,
        array $data = []
    ) {
        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $output,
            $logger,
            $importExportData,
            $jsonHelper,
            $dataSourceModel,
            $data
        );
        $this->_helper = $helper;
        $this->source = $source;
    }
}
