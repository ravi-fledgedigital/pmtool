<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\Brand\Setup\Patch\Data;


use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\BrandPageStoreInterface;


class MigrateBrandsPatch implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    public function __construct(
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.0.3';
    }

    public function apply()
    {
        $this->setup->startSetup();

        $connection = $this->setup->getConnection();

        $select = "SELECT brand_page_id, GROUP_CONCAT(store_id) as store_ids FROM "
            . $this->setup->getTable(BrandPageStoreInterface::TABLE_NAME)
            . " GROUP BY brand_page_id";

        foreach ($connection->query($select)->fetchAll() as $brandStore) {
            $connection->update(
                $this->setup->getTable(BrandPageInterface::TABLE_NAME),
                [BrandPageInterface::STORE_IDS => $brandStore[BrandPageInterface::STORE_IDS]],
                'brand_page_id = ' . $brandStore['brand_page_id']
            );
        }

        $select = $connection->select()->from(
            $this->setup->getTable(BrandPageInterface::TABLE_NAME),
            [
                BrandPageStoreInterface::BRAND_PAGE_ID,
                BrandPageStoreInterface::BRAND_TITLE,
                BrandPageStoreInterface::BRAND_DESCRIPTION,
                BrandPageStoreInterface::BRAND_SHORT_DESCRIPTION
            ]
        );

        $connection->delete(
            $this->setup->getTable(BrandPageStoreInterface::TABLE_NAME),
            'store_id = 0'
        );

        foreach ($connection->query($select)->fetchAll() as $brandPage) {
            $brandPage[BrandPageStoreInterface::STORE_ID] = 0;

            $connection->insertOnDuplicate(
                $this->setup->getTable(BrandPageStoreInterface::TABLE_NAME),
                $brandPage,
                [
                    BrandPageStoreInterface::STORE_ID,
                    BrandPageStoreInterface::BRAND_PAGE_ID
                ]
            );
        }

        $this->setup->endSetup();
    }
}
