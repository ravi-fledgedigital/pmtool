<?php

namespace Gdx\AdminTheme\Setup;

/**
 * @codeCoverageIgnore
 */
class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'gdx_admintheme/env_label/color',
            'value' => '#ff0000',
        ];
        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('core_config_data'), $data, ['value']);

        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'gdx_admintheme/env_label/text',
            'value' => '',
        ];
        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('core_config_data'), $data, ['value']);

        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'gdx_admintheme/env_label/text_color',
            'value' => '#FFFFFF',
        ];
        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('core_config_data'), $data, ['value']);
    }
}
