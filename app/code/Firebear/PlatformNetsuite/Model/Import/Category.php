<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as MagentoCategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Framework\Registry;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Framework\App\ObjectManager;

/**
 * Class Category
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class Category extends \Firebear\ImportExport\Model\Import\Category
{
    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $_helper;

    /**
     * @param Data                                                                      $jsonHelper
     * @param \Magento\ImportExport\Helper\Data                                         $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data                     $importData
     * @param Config                                                                    $config
     * @param ResourceConnection                                                        $resource
     * @param Helper                                                                    $resourceHelper
     * @param StringUtils                                                               $string
     * @param ProcessingErrorAggregatorInterface                                        $errorAggregator
     * @param CollectionFactory                                                         $categoryColFactory
     * @param CategoryProcessor                                                         $categoryProcessor
     * @param CategoryFactory                                                           $categoryFactory
     * @param ManagerInterface                                                          $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface                                $storeManager
     * @param CategoryRepositoryInterface                                               $categoryRepository
     * @param \Symfony\Component\Console\Output\ConsoleOutput                           $output
     * @param \Magento\Framework\Registry                                               $registry
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data                    $importFireData
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\Catalog\Model\ResourceModel\CategoryFactory                      $categoryResourceFactory
     * @param \Firebear\ImportExport\Helper\Additional                                  $additional
     * @param \Magento\Framework\App\ProductMetadata                                    $productMetadata
     * @param \Magento\Framework\View\Model\Layout\Update\ValidatorFactory              $validatorFactory
     * @param \Firebear\ImportExport\Helper\Data                                        $helper
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $categoryColFactory,
        CategoryProcessor $categoryProcessor,
        CategoryFactory $categoryFactory,
        ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        Registry $registry,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        SeparatorFormatterInterface $separatorFormatter,
        \Firebear\ImportExport\Helper\Data $helper,
        $validationState = null
    ) {
        $this->_helper = $helper;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $categoryColFactory,
            $categoryProcessor,
            $categoryFactory,
            $eventManager,
            $storeManager,
            $categoryRepository,
            $output,
            $registry,
            $importFireData,
            $attributeColFactory,
            $categoryResourceFactory,
            $additional,
            $productMetadata,
            $validatorFactory,
            $filterManager,
            $separatorFormatter,
            $validationState
        );
    }

    /**
     * @param $data
     * @return mixed
     */
    public function customBunchesData($data)
    {
        if (!empty($data['hidden'])) {
            $data['is_active'] = 'No';
        }
        return $data;
    }

}
