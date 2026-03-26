<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Setup\Patch\Data;

use Amasty\PDFCustom\Model\ResourceModel\Template as ResourceTemplate;
use Amasty\PDFCustom\Model\Source\PlaceForUse;
use Amasty\PDFCustom\Model\TemplateFactory;
use Magento\Email\Model\Template;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;

class CreateTemplatesData implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ConfigInterface
     */
    private $resourceConfig;

    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    public function __construct(
        ResourceConnection $resourceConnection,
        ConfigInterface $resourceConfig,
        ResourceInterface $moduleResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
        $this->moduleResource = $moduleResource;
    }
    
    public function apply(): void
    {
        $setupVersion = $this->moduleResource->getDBVersion('Amasty_PDFCustom');

        if (!$setupVersion) {
            $connection = $this->resourceConnection->getConnection();
            $templatesTableName = $this->resourceConnection->getTableName(ResourceTemplate::MAIN_TABLE);
            $templatesDefaultData = $this->createPdfTemplatesData();
            foreach ($templatesDefaultData as $bind) {
                $configCode = $bind['template_code_config'];
                unset($bind['template_code_config']);
                
                $connection->insert($templatesTableName, $bind);
                
                $this->resourceConfig->saveConfig(
                    $configCode,
                    $connection->lastInsertId($templatesTableName),
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    Store::DEFAULT_STORE_ID
                );
            }
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
    
    private function createPdfTemplatesData(): array
    {
        $templatesArray =
            [
                'ampdf_creditmemo_template' => [
                    'template_name' => __('Credit memo default Template'),
                    'place_for_use' => PlaceForUse::TYPE_CREDIT_MEMO
                ],
                'ampdf_invoice_template' => [
                    'template_name' => __('Invoice default Template'),
                    'place_for_use' => PlaceForUse::TYPE_INVOICE
                ],
                'ampdf_order_template' => [
                    'template_name' => __('Order default Template'),
                    'place_for_use' => PlaceForUse::TYPE_ORDER
                ],
                'ampdf_shipment_template' => [
                    'template_name' => __('Shipment default Template'),
                    'place_for_use' => PlaceForUse::TYPE_SHIPPING
                ]
            ];
        $templatesDefaultData = [];
        foreach ($templatesArray as $templateCode => $templateData) {
            $template = ObjectManager::getInstance()->create(Template::class);

            $template->setForcedArea($templateCode);

            $template->loadDefault($templateCode);
            $templateText = $template->getTemplateText();
            $templateStyles = $template->getTemplateStyles();
            $templatesDefaultData[] = [
                'template_code' => $templateData['template_name'],
                'template_text' => $templateText,
                'template_styles' => $templateStyles,
                'orig_template_code' => $templateCode,
                'place_for_use' => $templateData['place_for_use'],
                'template_code_config' => str_replace('_', '/', $templateCode)
            ];
        }

        return $templatesDefaultData;
    }
}
