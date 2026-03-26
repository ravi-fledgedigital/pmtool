<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\Customer\Setup\Patch\Data;

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
            ['TH','TH206','AMNART CHAROEN'],
            ['TH','TH187','ANG THONG'],
            ['TH','TH185','AYUDHAYA'],
            ['TH','TH180','Bangkok'],
            ['TH','TH207','BUNG KARN'],
            ['TH','TH200','BURIRUM'],
            ['TH','TH195','CHACHOENGSAO'],
            ['TH','TH189','CHAINAT'],
            ['TH','TH205','CHAIYAPHUM'],
            ['TH','TH193','CHANTABURI'],
            ['TH','TH219','CHIANGMAI'],
            ['TH','TH226','CHIANGRAI'],
            ['TH','TH191','CHONBURI'],
            ['TH','TH250','CHUMPHORN'],
            ['TH','TH215','KALASIN'],
            ['TH','TH237','KARNCHANABURI'],
            ['TH','TH209','KHON KAEN'],
            ['TH','TH245','KRABI'],
            ['TH','TH230','KUMPHENGPHET'],
            ['TH','TH221','LAMPANG'],
            ['TH','TH220','LAMPHUN'],
            ['TH','TH211','LOEI'],
            ['TH','TH186','LOP BURI'],
            ['TH','TH227','MAE HONGSORN'],
            ['TH','TH213','MAHASARAKAM'],
            ['TH','TH218','MUKDAHAN'],
            ['TH','N/ATH','NA'],
            ['TH','TH197','NAKHON NAYOK'],
            ['TH','TH239','NAKHON PATHOM'],
            ['TH','TH217','NAKHON PHANOM'],
            ['TH','TH199','NAKHON RACHASIMA'],
            ['TH','TH228','NAKHON SAWAN'],
            ['TH','TH244','NAKHON SRITHAMMARAT'],
            ['TH','TH224','NAN'],
            ['TH','TH257','NARATIWAT'],
            ['TH','TH208','NONG BUA LAM PHU'],
            ['TH','TH212','NONG KAI'],
            ['TH','TH183','NONTHABURI'],
            ['TH','TH179','Pathum Thani'],
            ['TH','TH254','PATTALUNG'],
            ['TH','TH255','PATTANI'],
            ['TH','TH225','PAYAO'],
            ['TH','TH235','PETCHABOON'],
            ['TH','TH242','PETCHABURI'],
            ['TH','TH246','PHANG-NGA'],
            ['TH','TH234','PHICHIT'],
            ['TH','TH233','PHITSANULOK'],
            ['TH','TH223','PHRAE'],
            ['TH','TH247','PHUKET'],
            ['TH','TH196','PRACHINBURI'],
            ['TH','TH243','PRACHUAB KIRIKHUN'],
            ['TH','TH236','RACHABURI'],
            ['TH','TH249','RANONG'],
            ['TH','TH192','RAYONG'],
            ['TH','TH214','Roi Et'],
            ['TH','TH216','SAKOL NAKHON'],
            ['TH','TH182','SAMUT PRAKARN'],
            ['TH','TH240','SAMUT SAKORN'],
            ['TH','TH241','SAMUT SONGKRAM'],
            ['TH','TH190','SARABURI'],
            ['TH','TH188','SINGBURI'],
            ['TH','TH202','SISAKET'],
            ['TH','TH251','SONG KHLA'],
            ['TH','TH198','SRAKAEW'],
            ['TH','TH252','STOON'],
            ['TH','TH232','SUKHOTHAI'],
            ['TH','TH238','SUPHAN BURI'],
            ['TH','TH248','SURATTHANI'],
            ['TH','TH201','SURIN'],
            ['TH','TH231','TAK'],
            ['TH','TH253','TRANG'],
            ['TH','TH194','TRAT'],
            ['TH','TH203','UBON RACHATHANI'],
            ['TH','TH210','UDONTHANI'],
            ['TH','TH229','UTHAI THANI'],
            ['TH','TH222','UTTARADIT'],
            ['TH','TH256','YALA'],
            ['TH','TH204','YASOTHORN']
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
