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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Block;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Api\Data\PageStoreInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;

class RelatedLandingPages extends Template implements IdentityInterface
{
    protected $_template = 'Magento_AdvancedSearch::search_data.phtml';

    private $resource;

    private $registry;

    private $request;

    private $storeManager;

    private $configProvider;

    private $relatedPages = null;

    private $linkMap = [];

    public function __construct(
        Context              $context,
        ResourceConnection   $resource,
        Registry             $registry,
        StoreManagerInterface $storeManager,
        ConfigProvider       $configProvider,
        array                $data = []
    ) {
        parent::__construct($context, $data);

        $this->resource       = $resource;
        $this->registry       = $registry;
        $this->request        = $context->getRequest();
        $this->storeManager   = $storeManager;
        $this->configProvider = $configProvider;
    }

    public function getTitle(): string
    {
        $title = $this->configProvider->getBlockTitle();

        return $title !== '' ? $title : (string)__('Related Searches');
    }

    public function getItems(): array
    {
        $items = [];

        foreach ($this->getRelatedPages() as $page) {
            $this->linkMap[$page['name']] = $page['url'];
            $items[] = new DataObject(['query_text' => $page['name']]);
        }

        return $items;
    }

    public function getLink(string $queryText): string
    {
        return $this->linkMap[$queryText] ?? '';
    }

    public function isShowResultsCount(): bool
    {
        return false;
    }

    public function getRelatedPages(): array
    {
        if ($this->relatedPages !== null) {
            return $this->relatedPages;
        }

        $this->relatedPages = [];

        if (!$this->configProvider->isRelatedPagesEnabled()) {
            return $this->relatedPages;
        }

        $context  = (string)$this->getData('context');
        $storeId  = (int)$this->storeManager->getStore()->getId();
        $maxLinks = $this->configProvider->getMaxLinks();

        switch ($context) {
            case 'product':
                $this->relatedPages = $this->getForProduct($storeId, $maxLinks);
                break;
            case 'category':
                $this->relatedPages = $this->getForCategory($storeId, $maxLinks);
                break;
            case 'landing':
                $this->relatedPages = $this->getForLanding($storeId, $maxLinks);
                break;
        }

        return $this->relatedPages;
    }

    public function getIdentities()
    {
        return ['mst_landing_page'];
    }

    public function getCacheKeyInfo()
    {
        $info   = parent::getCacheKeyInfo();
        $info[] = $this->getData('context');
        $info[] = $this->storeManager->getStore()->getId();

        $product = $this->registry->registry('current_product');
        if ($product) {
            $info[] = 'product_' . $product->getId();
        }

        $category = $this->registry->registry('current_category');
        if ($category) {
            $info[] = 'category_' . $category->getId();
        }

        $info[] = 'landing_' . $this->request->getParam('landing', 0);

        return $info;
    }

    private function getForProduct(int $storeId, int $maxLinks): array
    {
        $product = $this->registry->registry('current_product');
        if (!$product) {
            return [];
        }

        $select = $this->resource->getConnection()->select()
            ->from(['idx' => $this->resource->getTableName(ConfigProvider::INDEX_TABLE)], [])
            ->where('idx.product_id = ?', $product->getId())
            ->where('idx.store_id = ?', $storeId);

        return $this->queryPages($select, 'idx.page_id', $storeId, $maxLinks);
    }

    private function getForCategory(int $storeId, int $maxLinks): array
    {
        $category = $this->registry->registry('current_category');
        if (!$category) {
            return [];
        }

        $select = $this->resource->getConnection()->select()
            ->from(['idx' => $this->resource->getTableName(ConfigProvider::INDEX_TABLE)], [])
            ->join(
                ['ccp' => $this->resource->getTableName('catalog_category_product')],
                'idx.product_id = ccp.product_id',
                []
            )
            ->columns(['relevance' => new \Zend_Db_Expr('COUNT(DISTINCT idx.product_id)')])
            ->where('ccp.category_id = ?', $category->getId())
            ->where('idx.store_id = ?', $storeId)
            ->group('idx.page_id')
            ->order('relevance DESC');

        return $this->queryPages($select, 'idx.page_id', $storeId, $maxLinks);
    }

    private function getForLanding(int $storeId, int $maxLinks): array
    {
        $currentPageId = (int)$this->request->getParam('landing');
        if (!$currentPageId) {
            return [];
        }

        $indexTable = $this->resource->getTableName(ConfigProvider::INDEX_TABLE);

        $select = $this->resource->getConnection()->select()
            ->from(['idx1' => $indexTable], [])
            ->join(
                ['idx2' => $indexTable],
                'idx1.product_id = idx2.product_id AND idx1.store_id = idx2.store_id',
                []
            )
            ->columns(['relevance' => new \Zend_Db_Expr('COUNT(idx2.product_id)')])
            ->where('idx1.page_id = ?', $currentPageId)
            ->where('idx1.store_id = ?', $storeId)
            ->where('idx2.page_id != ?', $currentPageId)
            ->group('idx2.page_id')
            ->order('relevance DESC');

        return $this->queryPages($select, 'idx2.page_id', $storeId, $maxLinks);
    }

    /**
     * Appends page/store joins, columns, is_active filter, limit — executes and builds links.
     */
    private function queryPages(
        \Magento\Framework\DB\Select $select,
        string $pageIdColumn,
        int $storeId,
        int $maxLinks
    ): array {
        $select->join(
            ['p' => $this->resource->getTableName(PageInterface::MAIN_TABLE)],
            $pageIdColumn . ' = p.page_id',
            []
        )->joinLeft(
            ['s' => $this->resource->getTableName(PageStoreInterface::TABLE_NAME)],
            'p.page_id = s.page_id AND s.store_id = ' . $storeId,
            []
        )->columns([
            'page_id' => 'p.page_id',
            'name'    => new \Zend_Db_Expr('IFNULL(s.name, p.name)'),
            'url_key' => new \Zend_Db_Expr('IFNULL(s.url_key, p.url_key)'),
        ])->where(
            new \Zend_Db_Expr('IFNULL(s.is_active, p.is_active)') . ' = ?',
            1
        )->limit($maxLinks);

        $rows = $this->resource->getConnection()->fetchAll($select);

        return $this->buildLinks($rows);
    }

    private function buildLinks(array $rows): array
    {
        $suffix  = $this->configProvider->getUrlSuffix();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $pages   = [];

        foreach ($rows as $row) {
            $pages[] = [
                'name' => (string)$row['name'],
                'url'  => $baseUrl . $row['url_key'] . $suffix,
            ];
        }

        return $pages;
    }
}
