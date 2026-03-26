<?php
namespace OnitsukaTiger\KerryConNo\Setup;

/**
 * Class UpgradeSchema
 * @package OnitsukaTiger\KerryConNo\Setup
 */
class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $kerryTrackTable = $setup->getTable('kerry_shipping_track');
            $orderTable = $setup->getTable('sales_order');

            if ($setup->getConnection()->isTableExists($kerryTrackTable)) {
                $setup->getConnection()->dropForeignKey(
                    $kerryTrackTable,
                    $setup->getFkName($kerryTrackTable, 'unique_id', $orderTable, 'increment_id')
                );
                $setup->getConnection()->modifyColumn(
                    $kerryTrackTable,
                    'unique_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'save order_id, this column is not unique since version 1.0.1'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
