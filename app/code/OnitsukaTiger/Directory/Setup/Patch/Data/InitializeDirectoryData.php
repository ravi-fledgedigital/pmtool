<?php

namespace OnitsukaTiger\Directory\Setup\Patch\Data;

use Magento\Directory\Helper\Data;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class InitializeDirectoryData
 *
 * @package Magento\Directory\Setup\Patch
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

        /**
         * Fill table directory/country_region
         * Fill table directory/country_region_name for en_US locale
         */
        $data = [
            ['MY','MY-01','Johor'],
            ['MY','MY-02','Kedah'],
            ['MY','MY-03','Kelantan'],
            ['MY','MY-04','Malacca'],
            ['MY','MY-05','Negeri Sembilan'],
            ['MY','MY-06','Pahang'],
            ['MY','MY-07','Penang'],
            ['MY','MY-08','Perak'],
            ['MY','MY-09','Perlis'],
            ['MY','MY-12','Sabah'],
            ['MY','MY-13','Sarawak'],
            ['MY','MY-10','Selangor'],
            ['MY','MY-11','Terengganu'],
            ['MY','MY-14','Kuala Lumpur'],
            ['MY','MY-15','Labuan'],
            ['MY','MY-16','Putrajaya']
        ];
        foreach ($data as $row) {
            $bind = ['country_id' => $row[0], 'code' => $row[1], 'default_name' => $row[2]];
            $this->moduleDataSetup->getConnection()->insert(
                $this->moduleDataSetup->getTable('directory_country_region'),
                $bind
            );
            $regionId = $this->moduleDataSetup->getConnection()->lastInsertId(
                $this->moduleDataSetup->getTable('directory_country_region')
            );
            $bind = ['locale' => 'en_US', 'region_id' => $regionId, 'name' => $row[2]];
            $this->moduleDataSetup->getConnection()->insert(
                $this->moduleDataSetup->getTable('directory_country_region_name'),
                $bind
            );
        }
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
    public static function getVersion()
    {
        return '1.0.2';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
