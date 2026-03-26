<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Setup\Patch\Data;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\ResourceModel\Template;
use Amasty\PDFCustom\Model\Source\PlaceForUse;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class MigrateTemplatesFromConfigToTable implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ResourceConnection $resourceConnection,
        ResourceInterface $moduleResource,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->moduleResource = $moduleResource;
        $this->storeManager = $storeManager;
    }

    public function apply(): void
    {
        $setupVersion = $this->moduleResource->getDBVersion('Amasty_PDFCustom');
        
        //we need to migrate templates only if upgrade module from version less than 1.2.0
        if ($setupVersion && version_compare($setupVersion, '1.2.0', '<')) {
            $this->migrateConfigTemplates();
            $this->updatePlaceForUseForOtherTemplates();
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
    
    private function migrateConfigTemplates(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $templateTableName = $this->resourceConnection->getTableName(Template::MAIN_TABLE);
        $types = [
            PlaceForUse::TYPE_ORDER => 'order',
            PlaceForUse::TYPE_INVOICE => 'invoice',
            PlaceForUse::TYPE_SHIPPING => 'shipment',
            PlaceForUse::TYPE_CREDIT_MEMO => 'creditmemo',
        ];
        $templateToType = [];
        foreach ($types as $typeId => $typeName) {
            $templateToStore = $this->getTemplatesWithStoresByType(
                $typeName
            );
            foreach ($templateToStore as $templateId => $storeIds) {
                $storeIdsString = implode(',', $storeIds);
                if (array_key_exists($templateId, $templateToType)) {
                    $postfix = $this->getNewTemplateCodePostfix($typeName, $templateId);
                    $select = $connection->select()
                        ->from(
                            $templateTableName,
                            [
                                new \Zend_Db_Expr('CONCAT(template_code, " - ' . $postfix . '")'),
                                'template_text',
                                'template_styles',
                                'orig_template_code',
                                'orig_template_variables',
                                new \Zend_Db_Expr("'{$typeId}'"),
                                new \Zend_Db_Expr("'{$storeIdsString}'"),
                            ]
                        )->where('template_id = ?', $templateId);

                    $connection->query(
                        $connection->insertFromSelect(
                            $select,
                            $templateTableName,
                            [
                                'template_code',
                                'template_text',
                                'template_styles',
                                'orig_template_code',
                                'orig_template_variables',
                                'place_for_use',
                                'store_ids',
                            ]
                        )
                    );
                    continue;
                }

                $connection->update(
                    $templateTableName,
                    [
                        'store_ids' => $storeIdsString,
                        'place_for_use' => $typeId
                    ],
                    ['template_id = ?' => $templateId]
                );
                $templateToType[$templateId] = $typeId;
            }
        }
    }

    private function getTemplatesWithStoresByType(string $typeCode): array
    {
        $websites = $this->storeManager->getWebsites();
        $allStores = array_keys($this->storeManager->getStores(true));
        $connection = $this->resourceConnection->getConnection();
        $select = $connection
            ->select()
            ->from(['t' => $this->resourceConnection->getTableName('core_config_data')])
            ->where('path = ?', ConfigProvider::MODULE_SECTION . $typeCode . '/template')
            ->where('value != 0');

        $configs = $connection->fetchAll($select);

        $templateToStore = [];
        $usedStoreIds = [];

        foreach ($configs as $config) {
            if ($config['scope'] != ScopeInterface::SCOPE_STORES) {
                continue;
            }
            $templateId = $config['value'];
            $storeId = $config['scope_id'];
            $templateToStore[$templateId][$storeId] = $storeId;
            $usedStoreIds[$storeId] = $storeId;
        }

        foreach ($configs as $config) {
            if ($config['scope'] != ScopeInterface::SCOPE_WEBSITES || empty($websites[$config['scope_id']])) {
                continue;
            }
            $templateId = $config['value'];
            $website = $websites[$config['scope_id']];
            $storeIds = $website->getStoreIds();
            $storeIds = array_diff($storeIds, $usedStoreIds);
            foreach ($storeIds as $storeId) {
                $templateToStore[$templateId][$storeId] = $storeId;
                $usedStoreIds[$storeId] = $storeId;
            }
        }

        $storeIds = $allStores;
        $storeIds = array_diff($storeIds, $usedStoreIds);
        foreach ($configs as $config) {
            if ($config['scope'] != ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                continue;
            }
            $templateId = $config['value'];
            foreach ($storeIds as $storeId) {
                $templateToStore[$templateId][$storeId] = $storeId;
            }
        }

        return $templateToStore;
    }

    private function getNewTemplateCodePostfix(string $typeName, int $templateId): string
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->resourceConnection->getTableName(Template::MAIN_TABLE),
                ['template_code']
            )->where(
                'template_id = ?',
                $templateId
            );
        $templateName = $this->resourceConnection->getConnection()->fetchOne($select);

        $counter = 1;
        $postfix = '';
        do {
            if ($counter > 1) {
                $postfix = ' ' . $counter;
            }

            $select = $this->resourceConnection->getConnection()->select()
                ->from(
                    $this->resourceConnection->getTableName(Template::MAIN_TABLE),
                    [new \Zend_Db_Expr('COUNT(*)')]
                )->where(
                    'template_code = ?',
                    $templateName . ' - ' . $typeName . $postfix
                );

            $countRows = $this->resourceConnection->getConnection()->fetchOne($select);
            $counter++;
        } while ($countRows);

        return $typeName . $postfix;
    }

    private function updatePlaceForUseForOtherTemplates(): void
    {
        $templatesArray = [
            'ampdf_creditmemo_template' => PlaceForUse::TYPE_CREDIT_MEMO,
            'ampdf_invoice_template' => PlaceForUse::TYPE_INVOICE,
            'ampdf_order_template' => PlaceForUse::TYPE_ORDER,
            'ampdf_shipment_template' => PlaceForUse::TYPE_SHIPPING,
        ];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Template::MAIN_TABLE);

        foreach ($templatesArray as $templateCode => $placeForUse) {
            $connection->update(
                $tableName,
                [
                    'place_for_use' => $placeForUse
                ],
                [
                    'orig_template_code = ?' => $templateCode,
                    'place_for_use = 0'
                ]
            );
        }
    }
}
