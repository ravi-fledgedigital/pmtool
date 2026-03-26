<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Setup\Patch\Data;

use Amasty\PDFCustom\Model\ResourceModel\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeCssIncludeInTemplates implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(Template::MAIN_TABLE);
        $replace = 'REPLACE(template_text, "Amasty_PDFCustom/css/ampdf.css", "Amasty_PDFCustom::css/ampdf.css")';
        $connection->update(
            $table,
            [
                'template_text' => new \Zend_Db_Expr($replace)
            ]
        );
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
