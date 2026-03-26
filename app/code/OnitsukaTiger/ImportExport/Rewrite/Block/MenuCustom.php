<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\ImportExport\Rewrite\Block;

use Magento\Backend\Block\MenuItemChecker;
use Magento\Backend\Block\AnchorRenderer;
use Magento\Framework\App\ResourceConnection;

class MenuCustom extends \Magento\Backend\Block\Menu
{
    const WEBSITE = [
        '[KR]' => 'OnitsukaTiger_ImportExport::korea',
        '[SG]' => 'OnitsukaTiger_ImportExport::singapore',
        '[MY]' => 'OnitsukaTiger_ImportExport::malaysia',
        '[TH]' => 'OnitsukaTiger_ImportExport::thailand',
        '[IN]' => 'OnitsukaTiger_ImportExport::indonesia'
    ];

    const TABLES = ['firebear_import_jobs', 'firebear_export_jobs'];

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    protected $codeWebSite = 'randomstring';

    /**
     * @var MenuItemChecker
     */
    private $menuItemChecker;

    /**
     * @var AnchorRenderer
     */
    private $anchorRenderer;

    /**
     * @var \Magento\Framework\App\Route\ConfigInterface
     */
    private $routeConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\UrlInterface $url
     * @param \Magento\Backend\Model\Menu\Filter\IteratorFactory $iteratorFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Backend\Model\Menu\Config $menuConfig
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param array $data
     * @param MenuItemChecker|null $menuItemChecker
     * @param AnchorRenderer|null $anchorRenderer
     * @param \Magento\Framework\App\Route\ConfigInterface|null $routeConfig
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\UrlInterface $url,
        \Magento\Backend\Model\Menu\Filter\IteratorFactory $iteratorFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Backend\Model\Menu\Config $menuConfig,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        ResourceConnection $resource,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        array $data = [],
        MenuItemChecker $menuItemChecker = null,
        AnchorRenderer $anchorRenderer = null,
        \Magento\Framework\App\Route\ConfigInterface $routeConfig = null
    ) {
        $this->_url = $url;
        $this->_iteratorFactory = $iteratorFactory;
        $this->_authSession = $authSession;
        $this->_menuConfig = $menuConfig;
        $this->_localeResolver = $localeResolver;
        $this->_resource = $resource;
        $this->_backendUrl = $backendUrl;
        $this->menuItemChecker =  $menuItemChecker;
        $this->anchorRenderer = $anchorRenderer;
        $this->routeConfig = $routeConfig ?:
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\App\Route\ConfigInterface::class);
        parent::__construct($context, $url, $iteratorFactory, $authSession, $menuConfig, $localeResolver, $data, $menuItemChecker, $anchorRenderer, $routeConfig);
    }

    /**
     * Add sub menu HTML code for current menu item
     *
     * @param \Magento\Backend\Model\Menu\Item $menuItem
     * @param int $level
     * @param int $limit
     * @param int|null $id
     * @return string HTML code
     */
    protected function _addSubMenu($menuItem, $level, $limit, $id = null)
    {
        $output = '';
        if (!$menuItem->hasChildren() && !in_array($menuItem->getId(), self::WEBSITE)) {
            return $output;
        }

        $output .= '<div class="submenu"' . ($level == 0 && isset($id) ? ' aria-labelledby="' . $id . '"' : '') . '>';
        $colStops = [];
        if ($level == 0 && $limit) {
            $colStops = $this->_columnBrake($menuItem->getChildren(), $limit);
            $output .= '<strong class="submenu-title">' . $this->_getAnchorLabel($menuItem) . '</strong>';
            $output .= '<a href="#" class="action-close _close" data-role="close-submenu"></a>';
        }

        // custom menu admin import export
        if (in_array($menuItem->getId(), self::WEBSITE)) {
            foreach (self::WEBSITE as $key => $val) {
                if ($menuItem->getId() == $val) {
                    $this->codeWebSite = $key;
                }
            }
            $items = $this->getDataItems(self::TABLES);
            $output .= $this->getChildHtmlCustom($items);
        }else{
            $output .= $this->renderNavigation($menuItem->getChildren(), $level + 1, $limit, $colStops);
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * @param $items
     * @return string
     */
    public function getChildHtmlCustom($items) {
        $html = '';
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $action => $job) {
                $active = '';
                $actionJob = 'import/export_job/edit';
                if ($action == 'import') {
                    $active = 'active';
                    $actionJob = 'import/job/edit';
                }
                $html .= '<div class="action job-custom '.$active.'"><h3>'.$action.'</h3>';
                    $html .= '<ul class="menu">';
                    foreach ($job as $item) {
                        $html .= '<li class="item-actions-logmenu level-1">';
                        $html .= '<a href="'.$this->_backendUrl->getUrl("$actionJob", ['entity_id' => $item['entity_id']]).'" class=""><span>'.$item['title'].'</span></a>';
                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                $html .= '</div>';
            }
            $html .= '<script type="text/javascript">
            require([
                "jquery"
            ], function ($) {
                "use strict";
                $( "#nav" ).find(".action.job-custom").click(function() {
                  if ($(this).hasClass("active")) {
                      $(this).removeClass("active")
                  }else{
                      $(this).addClass("active")
                  }
                });
            });
            </script>';
        }
        return $html;
    }

    /**
     * @param $tables
     * @return array
     */
    public function getDataItems($tables) {
        $items = [];
        foreach ($tables as $table) {
            $key = 'export';
            if ($table == 'firebear_import_jobs') {
                $key = 'import';
            }
            $connection = $this->_resource->getConnection();
            $select = $connection->select()->from(
                $table,
                ['entity_id', 'title']
            )->where('title LIKE "%'.$this->codeWebSite.'%" OR title LIKE "%GLOBAL%"');
            $items[$key] = $connection->fetchAll($select);
        }
        return $items;
    }
}
