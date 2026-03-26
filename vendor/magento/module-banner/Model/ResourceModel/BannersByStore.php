<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Model\ResourceModel;

use Magento\Banner\Model\Config;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Zend_Db_Select_Exception;

/**
 *  Class returns banners content based on provided store and banner ids
 */
class BannersByStore
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var FilterProvider
     */
    private FilterProvider $filterProvider;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @param ResourceConnection $resource
     * @param FilterProvider $filterProvider
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ResourceConnection $resource,
        FilterProvider     $filterProvider,
        ManagerInterface   $eventManager
    ) {
        $this->connection = $resource->getConnection();
        $this->filterProvider = $filterProvider;
        $this->eventManager = $eventManager;
    }

    /**
     * Return types as array
     *
     * @param string|array|null $types
     * @return array
     */
    private function getTypes(string|array|null $types): array
    {
        if (is_array($types)) {
            return $types;
        }

        return empty($types) ? [] : explode(',', $types);
    }

    /**
     * Get select for banner content by store
     *
     * @param array $bannerIds
     * @param int $storeId
     * @return Select
     */
    private function getSelect(array $bannerIds, int $storeId): Select
    {
        $select = $this->connection->select()->from(
            ['main_table' => $this->connection->getTableName('magento_banner_content')],
            ['banner_id as id',  'main_table.banner_content as content']
        )->where(
            'main_table.banner_id IN (?)',
            $bannerIds,
            \Zend_Db::INT_TYPE
        )->where(
            'main_table.store_id = ' . $storeId
        );
        $select->joinInner(
            ['banner' => $this->connection->getTableName('magento_banner')],
            'main_table.banner_id = ' . 'banner.banner_id',
            ['types']
        );

        $this->eventManager->dispatch(
            'magento_banner_resource_banner_content_select_init',
            ['select' => $select, 'banner_id' => $bannerIds]
        );

        return $select;
    }

    /**
     * Get banners contents by specific store id
     *
     * @param array $bannerIds
     * @param int $storeId
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    private function getBannerContentsByStore(array $bannerIds, int $storeId): array
    {
        $defaultStoreSelect = $this->getSelect($bannerIds, 0);
        $storeSelect = $this->getSelect($bannerIds, $storeId);
        $select = $this->connection->select()->union([$defaultStoreSelect, $storeSelect]);
        return $this->connection->fetchAll($select);
    }

    /**
     * Return formatted content and types
     *
     * @param array $banners
     * @return array
     * @throws \Exception
     */
    private function getBannersFormattedContent(array $banners): array
    {
        $formattedBanners = $emptyContentBanners = [];
        foreach ($banners as $banner) {
            $banner['types'] = $this->getTypes($banner['types']);
            if (!empty($banner['content'])) {
                $banner['content'] = $this->filterProvider->getPageFilter()->filter($banner['content']);
            } else {
                $emptyContentBanners[$banner['id']] = null;
            }
            $formattedBanners[$banner['id']] = $banner;
        }
        return [$formattedBanners, $emptyContentBanners];
    }

    /**
     * Get banners by store
     *
     * @param array $bannerIds
     * @param int $storeId
     * @return array
     * @throws \Exception
     */
    public function execute(array $bannerIds, int $storeId): array
    {
        $banners = $this->getBannerContentsByStore($bannerIds, $storeId);
        return $this->getBannersFormattedContent($banners);
    }
}
