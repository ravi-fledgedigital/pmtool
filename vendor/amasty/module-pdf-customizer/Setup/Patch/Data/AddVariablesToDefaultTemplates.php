<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Setup\Patch\Data;

use Amasty\PDFCustom\Model\ResourceModel\Template;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddVariablesToDefaultTemplates implements DataPatchInterface
{
    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        TemplateFactory $templateFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->templateFactory = $templateFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function apply(): void
    {
        $templatesArray = [
            'ampdf_creditmemo_template' => __('Credit memo default Template'),
            'ampdf_invoice_template' => __('Invoice default Template'),
            'ampdf_order_template' => __('Order default Template'),
            'ampdf_shipment_template' => __('Shipment default Template')
        ];

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Template::MAIN_TABLE);
        foreach ($templatesArray as $templateCode => $templateName) {
            $template = $this->templateFactory->create();
            $template->setForcedArea($templateCode);
            $template->loadDefault($templateCode);

            $bind = ['orig_template_variables' => $template->getData('orig_template_variables')];

            $connection->update($tableName, $bind, ['template_code = ?' => $templateName]);
        }
    }
    
    public static function getDependencies(): array
    {
        return [CreateTemplatesData::class];
    }
    
    public function getAliases(): array
    {
        return [];
    }
}
