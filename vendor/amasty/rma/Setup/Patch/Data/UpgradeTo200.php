<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Setup\Patch\Data;

use Amasty\Rma\Setup\Operation\UpgradeTo200 as Upgrade200;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeTo200 implements DataPatchInterface
{
    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var Upgrade200
     */
    private $upgradeDataTo200;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        ResourceInterface $moduleResource,
        ModuleDataSetupInterface $moduleDataSetup,
        Upgrade200 $upgradeDataTo200,
        State $appState
    ) {
        $this->moduleResource = $moduleResource;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->upgradeDataTo200 = $upgradeDataTo200;
        $this->appState = $appState;
    }

    public function apply()
    {
        $setupDataVersion = $this->moduleResource->getDataVersion('Amasty_Rma');

        // Check if module was already installed or not.
        // If setup_version present in DB then we don't need to install fixtures, because setup_version is a marker.
        if (!$setupDataVersion || version_compare($setupDataVersion, '2.0.0', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this->upgradeDataTo200, 'execute'],
                [$this->moduleDataSetup, $setupDataVersion]
            );
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
