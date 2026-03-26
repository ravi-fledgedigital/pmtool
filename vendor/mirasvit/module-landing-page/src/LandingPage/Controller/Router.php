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

namespace Mirasvit\LandingPage\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Url;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Model\Url\UrlParser;

class Router implements RouterInterface
{
    private $actionFactory;

    private $pageRepository;

    private $eventManager;

    private $filterRepository;

    private $storeManager;

    private $urlParser;

    public function __construct(
        FilterRepository      $filterRepository,
        PageRepository        $pageRepository,
        ActionFactory         $actionFactory,
        EventManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        UrlParser             $urlParser
    ) {
        $this->filterRepository = $filterRepository;
        $this->pageRepository   = $pageRepository;
        $this->actionFactory    = $actionFactory;
        $this->eventManager     = $eventManager;
        $this->storeManager     = $storeManager;
        $this->urlParser        = $urlParser;
    }

    public function match(RequestInterface $request)
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $result   = [];
        $pathInfo = $request->getPathInfo();

        $result = $this->urlParser->match($pathInfo);

        if ($result) {
            $params = $result->getData('params');
            $request
                ->setAlias(
                    Url::REWRITE_REQUEST_PATH_ALIAS,
                    ltrim($request->getOriginalPathInfo(), '/')
                )
                ->setModuleName($result->getModuleName())
                ->setControllerName($result->getControllerName())
                ->setActionName($result->getActionName())
                ->setParams($params);

            return $this->actionFactory->create(
                'Magento\Framework\App\Action\Forward'
            );
        }

        return false;
    }
}
