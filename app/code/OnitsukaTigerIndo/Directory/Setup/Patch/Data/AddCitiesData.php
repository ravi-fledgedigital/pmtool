<?php
declare(strict_types=1);

namespace OnitsukaTigerIndo\Directory\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddCitiesData implements DataPatchInterface, PatchRevertableInterface
{
    const FILE_NAME = 'cities.csv';
    const TABLE_NAME = 'directory_country_cities';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $vendorDir = dirname(__FILE__);
        $vendorDir = str_replace('Setup/Patch/Data', 'Data', $vendorDir);
        $fileName = $vendorDir.'/'.self::FILE_NAME;
        $handle = fopen($fileName,"r");

        $cities = [];
        $n = 1;
        while ($data= fgetcsv($handle, 100, ","))
        {
            $cities[] = [
                'entity_id' => $n,
                'province_code' => $data[0],
                'city_id' => $n,
                'city_name' => $data[2],
            ];
            $n++;
        }

        fclose($handle);

        $connection->insertMultiple(
            self::TABLE_NAME,
            $cities
        );

        $connection->endSetup();

    }

    /**
     *
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}

