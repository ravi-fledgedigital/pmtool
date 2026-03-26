<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Setup\Patch\Data;

use Amasty\PDFCustom\Model\ComponentChecker;
use Amasty\PDFCustom\Model\ConfigProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DisableModuleAccordingToComponentExists implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ComponentChecker
     */
    private $componentChecker;

    public function __construct(
        ResourceConnection $resourceConnection,
        ComponentChecker $componentChecker
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->componentChecker = $componentChecker;
    }
    
    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $coreConfigDataTable = $this->resourceConnection->getTableName('core_config_data');
        $select = $connection
            ->select()
            ->from(['t' => $coreConfigDataTable])
            ->where('path = ?', ConfigProvider::MODULE_SECTION . ConfigProvider::XPATH_ENABLED);

        $config = $connection->fetchRow($select);
        $isComponentsExist = $this->componentChecker->isComponentsExist();
        if (!empty($config['value']) && !$isComponentsExist) {
            $connection->update(
                $coreConfigDataTable,
                ['value' => '0'],
                ['config_id = ?' => $config['config_id']]
            );
        }
    }
    
    public static function getDependencies(): array
    {
        return [];
    }
    
    public function getAliases(): array
    {
        return [];
    }
}
