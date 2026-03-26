<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2014 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Page\Edit\Tab;

use InvalidArgumentException;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Chooser as CMSHierarchyBlock;
use Magento\VersionsCms\Helper\Hierarchy as HierarchyHelper;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;

/**
 * Cms Page Edit Hierarchy Tab Block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Hierarchy extends Template implements TabInterface
{
    /**
     * Array of nodes for tree
     *
     * @var array|null
     */
    protected $_nodes = null;

    /**
     * @var HierarchyHelper
     */
    protected $_cmsHierarchy;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CollectionFactory
     */
    protected $_nodeCollectionFactory;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var string
     */
    protected $_template = 'page/tab/hierarchy.phtml';

    /**
     * @var CMSHierarchyBlock
     */
    protected $hierarchyBlock;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * constant root category
     */
    protected const WEBSITE_ROOT_ID = 2;

    /**
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param HierarchyHelper $cmsHierarchy
     * @param Registry $registry
     * @param CollectionFactory $nodeCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param CMSHierarchyBlock $hierarchyBlock
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        HierarchyHelper $cmsHierarchy,
        Registry $registry,
        CollectionFactory $nodeCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = [],
        CMSHierarchyBlock $hierarchyBlock = null
    ) {
        $this->_jsonDecoder = $jsonDecoder;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        $this->_cmsHierarchy = $cmsHierarchy;
        $this->hierarchyBlock = $hierarchyBlock ?: ObjectManager::getInstance()
            ->get(CMSHierarchyBlock::class);
        $this->_nodeCollectionFactory = $nodeCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current page instance
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->_coreRegistry->registry('cms_page');
    }

    /**
     * Retrieve Hierarchy JSON string
     *
     * @return string
     */
    public function getNodesJson()
    {
        return $this->_jsonEncoder->encode($this->getNodes());
    }

    /**
     * Prepare nodes data from DB  all from session if error occurred.
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getNodes()
    {
        if ($this->_nodes === null) {
            $this->_nodes = [];
            $data = null;
            try {
                $jsonData = $this->getPage()->getNodesData();
                if ($jsonData) {
                    $data = $this->_jsonDecoder->decode($jsonData);
                }
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (InvalidArgumentException $e) {
                // continue and use the collection to get the node data
            }

            /** @var Collection $collection */
            $collection = $this->_nodeCollectionFactory
                ->create()
                ->joinCmsPage()
                ->setOrderByLevel()
                ->joinPageExistsNodeInfo(
                    $this->getPage()
                );

            if (is_array($data)) {
                foreach ($data as $v) {
                    if (isset($v['page_exists'])) {
                        $pageExists = (bool)$v['page_exists'];
                    } else {
                        $pageExists = false;
                    }
                    $node = [
                        'node_id' => $v['node_id'],
                        'parent_node_id' => $v['parent_node_id'],
                        'label' => $this->_escaper->escapeHtml($v['label']),
                        'page_exists' => $pageExists,
                        'page_id' => $v['page_id'],
                        'current_page' => (bool)$v['current_page'],
                    ];
                    $item = $collection->getItemById($v['node_id']);
                    if ($item) {
                        $node['assigned_to_stores'] = $this->getPageStoreIds($item);
                    } else {
                        $node['assigned_to_stores'] = [];
                    }

                    $this->_nodes[] = $node;
                }
            } else {
                foreach ($collection as $item) {
                    if ($item->getLevel() == Node::NODE_LEVEL_FAKE) {
                        continue;
                    }
                    /* @var $item Node */
                    $node = [
                        'node_id' => $item->getId(),
                        'parent_node_id' => $item->getParentNodeId(),
                        'label' => $this->_escaper->escapeHtml($item->getLabel()),
                        'store_label' => $this->getNodeStoreName((int)$item->getScopeId(), $item->getScope()),
                        'page_exists' => (bool)$item->getPageExists(),
                        'page_id' => $item->getPageId(),
                        'current_page' => (bool)$item->getCurrentPage(),
                        'sort_order' => (int)$item->getSortOrder() ?? 0,
                        'assigned_to_stores' => $this->getPageStoreIds($item),
                    ];
                    $this->_nodes[] = $node;
                }
            }
        }
        return $this->_nodes;
    }

    /**
     * Return store name for node by scope_id
     *
     * @param int $scopeId
     * @param string $scopeCode
     * @return string
     * @throws NoSuchEntityException
     */
    private function getNodeStoreName(int $scopeId, string $scopeCode = Node::NODE_SCOPE_STORE)
    {
        if ($scopeCode === Node::NODE_SCOPE_WEBSITE) {
            $scope = $this->storeManager->getWebsite($scopeId);
        } else {
            $scope = $this->storeManager->getStore($scopeId);
        }

        if (!$scope->getId()) {
            return 'All Store Views';
        }
        return $scope->getName();
    }

    /**
     * Return page store ids.
     *
     * @param object $node
     * @return array
     */
    public function getPageStoreIds($node)
    {
        if (!$node->getPageId() || !$node->getPageInStores()) {
            return [];
        }
        return explode(',', $node->getPageInStores());
    }

    /**
     * Forced nodes setter
     *
     * @param array $nodes New nodes array
     * @return $this
     */
    public function setNodes($nodes)
    {
        if (is_array($nodes)) {
            $this->_nodes = $nodes;
        }

        return $this;
    }

    /**
     * Retrieve ids of selected nodes from two sources.
     * First is from prepared data from DB.
     * Second source is data from page model in case we had error.
     *
     * @return string
     */
    public function getSelectedNodeIds()
    {
        if (!$this->getPage()->hasData('node_ids')) {
            $ids = [];

            foreach ($this->getNodes() as $node) {
                if (isset($node['page_exists']) && $node['page_exists']) {
                    $ids[] = $node['node_id'];
                }
            }
            return implode(',', $ids);
        }

        return $this->getPage()->getData('node_ids');
    }

    /**
     * Prepare json string with current page data
     *
     * @return string
     */
    public function getCurrentPageJson()
    {
        $title = $this->_escaper->escapeHtml($this->getPage()->getTitle());
        $data = ['label' => $title, 'id' => $this->getPage()->getId()];

        return $this->_jsonEncoder->encode($data);
    }

    /**
     * Retrieve Tab label
     *
     * @return Phrase
     */
    public function getTabLabel()
    {
        return __('Hierarchy');
    }

    /**
     * Retrieve Tab title
     *
     * @return Phrase
     */
    public function getTabTitle()
    {
        return __('Hierarchy');
    }

    /**
     * Check is can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        if (!$this->getPage()->getId() || !$this->_cmsHierarchy->isEnabled() || !$this->_authorization->isAllowed(
            'Magento_VersionsCms::hierarchy'
        )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get Category html
     *
     * @return void
     */
    public function getCMSPageCategory()
    {
        $hierarchyNodes = $this->getNodes();
        // Pushing extra Website root as tree root
        $websiteRoot[] = [
            'node_id'=> self::WEBSITE_ROOT_ID,
            'selected' => true,
            'parent_node_id' =>'',
            'label' => 'Website Root',
            'store_label' => ""
        ];
        if (count($hierarchyNodes) > 0) {
            $tree = $this->hierarchyBlock->buildTree($hierarchyNodes);
            $websiteRoot[0]['children'] = $tree;
        }
        return $this->getCMSPageTree($websiteRoot);
    }

    /**
     * Get the tree for category
     *
     * @param array $tree
     * @return string
     */
    public function getCMSPageTree(array $tree) : string
    {
        $html = "<ul>";
        foreach ($tree as $category) {
            $label  = $this->getNodeLabel($category);
            $attribute  = $this->getLiAttributes($category);
            $html .= "<li id='" . $category['node_id'] . "' " . $attribute . ">";
            $html .= "<span>" . $label . "</span>";
            if (isset($category["children"])) {
                $html .= $this->getCMSPageTree($category["children"]);
            }
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    /**
     * Setting <li> attributes
     *
     * @param array $cat
     * @return string
     */
    protected function getLiAttributes(array $cat): string
    {
        $selected       = false;
        $page_exists    = $cat['page_exists'] ?? "";
        $parent_node_id = $cat['parent_node_id'] ?? "";
        $page_id        = $cat['page_id'] ?? "";
        $current_page   = $cat['current_page'] ?? "";
        $attribute      = "parent_node_id='" . $parent_node_id . "' ";
        $attribute     .= "page_exists='" . $page_exists . "' ";
        $attribute     .= "page_id='" . $page_id . "' ";
        $attribute     .= "current_page='" . $current_page . "' ";

        if (isset($cat['current_page']) && $cat['current_page']) {
            $attribute .= "class='jstree-no-checkboxes' ";
        }
        if ($this->isNodeSelected($cat['node_id'])) {
            $selected = true;
        }
        if ($cat['label'] === "Website Root"
            && $this->getPage()->getWebsiteRoot() == 1
            && $this->isWebsiteRootSelected()) {
            $selected = true;
        }
        $attribute .= $selected ? "data-jstree='{\"selected\":true}'" : "";
        return $attribute;
    }

    /**
     * Return true if node is checked
     *
     * @param int $nodeId
     * @return bool
     */
    public function isNodeSelected(int $nodeId) : bool
    {
        $selectedIds = $this->getSelectedNodeIds();
        if (!empty($selectedIds) && !empty($nodeId)) {
            $idArray = explode(",", $selectedIds);
            if (in_array($nodeId, $idArray)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return the category label
     *
     * @param array $arr
     * @return string
     */
    private function getNodeLabel(array $arr) : string
    {
        $label  = $arr['label'];
        $label .=  $this->getItalicGreyText($arr['store_label']);
        if ((isset($arr['current_page']) && $arr['current_page'])
            || $arr['label'] === "Website Root") {
            $label  = $arr['label'];
        }
        return $label;
    }

    /**
     * Get italic grey text
     *
     * @param string $text
     * @return string
     */
    protected function getItalicGreyText(string $text) : string
    {
        $label = "";
        if (!empty($text)) {
            $label  = " <i style='color: grey'>(";
            $label .= $text;
            $label .= ")</i>";
        }
        return $label;
    }

    /**
     * By default Magento saves website root (FIRST TIME) as 1 even if you didn't select any checkbox
     *
     * @return bool
     */
    protected function isWebsiteRootSelected() : bool
    {
        if (count($this->getNodes()) > 0) {
            foreach ($this->getNodes() as $val) {
                if (isset($val['current_page'])) {
                    if ($val['parent_node_id'] == ""
                        && $val['current_page']) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Create nodes array for textbox
     *
     * @return false|string|void
     */
    public function getPrefilledData()
    {
        $nodesArray = $this->getNodes();
        if (count($nodesArray) > 0) {
            $arr = [];
            foreach ($nodesArray as $val) {
                $label = $val['label'] . $this->getItalicGreyText($val['store_label']);
                $arr['node_id']         = $val['node_id'] ?? null;
                $arr['page_id']         = $val['page_id'] ?? null;
                $arr['parent_node_id']  = $val['parent_node_id'] ?? null;
                $arr['label']           = $label;
                $arr['sort_order']      = $val['sort_order'];
                $arr['current_page']    = $val['current_page'];
                $arr['page_exists']     = $val['page_exists'];
                $newNodeArr[$val['node_id']] = $arr;
            }
            ksort($newNodeArr);
        } else {
            $newNodeArr = [];
        }
        return json_encode($newNodeArr, JSON_FORCE_OBJECT);
    }
}
