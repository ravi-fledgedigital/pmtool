<?php

namespace OnitsukaTigerIndo\Directory\Setup\Patch\Data;

use Magento\Directory\Helper\DataFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class InitializeIndoDirectoryData
 * @package OnitsukaTigerIndo\Directory\Setup\Patch\Data
 */
class InitializeIndoDirectoryData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var DataFactory
     */
    private $directoryDataFactory;

    /**
     * InitializeIndoDirectoryData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DataFactory $directoryDataFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DataFactory $directoryDataFactory
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
            ['ID','Bali','ID-BA'],
            ['ID','Bangka Belitung','ID-BB'],
            ['ID','Banten','ID-BT'],
            ['ID','Bengkulu','ID-BE'],
            ['ID','DI Yogyakarta','ID-YO'],
            ['ID','DKI Jakarta','ID-JK'],
            ['ID','Gorontalo','ID-GO'],
            ['ID','Jambi','ID-JA'],
            ['ID','Jawa Barat','ID-JB'],
            ['ID','Jawa Tengah','ID-JT'],
            ['ID','Jawa Timur','ID-JI'],
            ['ID','Kalimantan Barat','ID-KB'],
            ['ID','Kalimantan Selatan','ID-KS'],
            ['ID','Kalimantan Tengah','ID-KT'],
            ['ID','Kalimantan Timur','ID-KI'],
            ['ID','Kalimantan Utara','ID-KU'],
            ['ID','Kepulauan Riau','ID-KR'],
            ['ID','Lampung','ID-LA'],
            ['ID','Maluku','ID-MA'],
            ['ID','Maluku Utara','ID-MU'],
            ['ID','Nanggroe Aceh Darussalam (NAD)','ID-AC'],
            ['ID','Nusa Tenggara Barat (NTB)','ID-NB'],
            ['ID','Nusa Tenggara Timur (NTT)','ID-NT'],
            ['ID','Papua','ID-PA'],
            ['ID','Papua Barat','ID-PB'],
            ['ID','Riau','ID-RI'],
            ['ID','Sulawesi Barat','ID-SR'],
            ['ID','Sulawesi Selatan','ID-SN'],
            ['ID','Sulawesi Tengah','ID-ST'],
            ['ID','Sulawesi Tenggara','ID-SG'],
            ['ID','Sulawesi Utara','ID-SA'],
            ['ID','Sumatera Barat','ID-SB'],
            ['ID','Sumatera Selatan','ID-SS'],
            ['ID','Sumatera Utara','ID-SU']
        ];
        foreach ($data as $row) {
            $bind = ['country_id' => $row[0], 'code' => $row[2], 'default_name' => $row[1]];
            $this->moduleDataSetup->getConnection()->insert(
                $this->moduleDataSetup->getTable('directory_country_region'),
                $bind
            );
            $regionId = $this->moduleDataSetup->getConnection()->lastInsertId(
                $this->moduleDataSetup->getTable('directory_country_region')
            );
            $bind = ['locale' => 'en_US', 'region_id' => $regionId, 'name' => $row[1]];
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
