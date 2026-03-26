<?php

namespace OnitsukaTiger\Malaysia\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class InitializeDirectoryData
 *
 * @package Magento\Malaysia\Setup\Patch
 */
class InitializeDirectoryData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Directory\Helper\DataFactory
     */
    private $directoryDataFactory;

    /**
     * InitializeDirectoryData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Directory\Helper\DataFactory $directoryDataFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Directory\Helper\DataFactory $directoryDataFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->directoryDataFactory = $directoryDataFactory;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('directory_country_region'),
            ['country_id' => 'MY', 'code' => 'MY-15', 'default_name' => 'Wilayah Persekutuan Labuan']
        );

        $regionId = $this->moduleDataSetup->getConnection()->lastInsertId(
            $this->moduleDataSetup->getTable('directory_country_region')
        );

        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('directory_country_region_name'),
            ['locale' => 'en_US', 'region_id' => $regionId, 'name' => 'Wilayah Persekutuan Labuan']
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
