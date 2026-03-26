<?php
declare(strict_types=1);

namespace OnitsukaTiger\Aitoc\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateSmtpLog implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * UpdateOrderAddress constructor.
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
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $smtpLogTable = $this->moduleDataSetup->getTable('aitoc_smtp_log');

        if ($this->moduleDataSetup->getConnection()->isTableExists($smtpLogTable) == true){
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($smtpLogTable),
                'invoice_pdf_content',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '255',
                    'nullable' => true,
                    'comment' => 'Invoice Pdf Content'
                ]
            );
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($smtpLogTable),
                'dispatch_pdf_content',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '255',
                    'nullable' => true,
                    'comment' => 'Dispatch Pdf Content'
                ]
            );
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
