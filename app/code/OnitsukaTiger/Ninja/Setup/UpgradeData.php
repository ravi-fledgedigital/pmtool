<?php

namespace OnitsukaTiger\Ninja\Setup;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{

    public function upgrade(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    )
    {
        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $this->addTrackingIdTextIndex($setup);
        }
    }

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     */
    protected function addTrackingIdTextIndex(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup
    )
    {
        $setup->getConnection()->addIndex(
            'ninja_order', //table name
            'ninja_order_tracking_id_text_index',    // index name
            [
                'tracking_id'
            ],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
        );
    }
}
