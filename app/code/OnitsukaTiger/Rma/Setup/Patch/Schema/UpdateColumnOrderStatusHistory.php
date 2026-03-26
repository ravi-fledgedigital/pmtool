<?php declare(strict_types=1);

namespace OnitsukaTiger\Rma\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateColumnOrderStatusHistory implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * UpdateColumnCreditMemo constructor.
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

        $creditMemoTable = $this->moduleDataSetup->getTable('sales_order_status_history');

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable($creditMemoTable),
            'is_admin',
            [
                'type' => Table::TYPE_BOOLEAN,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Note by admin'
            ]
        );

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
