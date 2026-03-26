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

namespace Mirasvit\Brand\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Mirasvit\Brand\Service\BrandUrlService;
use Magento\Framework\App\ResponseInterface;
use Mirasvit\Brand\Model\Config\Config;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Redirect;

class Router implements RouterInterface
{
    private $actionFactory;

    private $urlService;

    private $response;

    private $config;

    public function __construct(
        BrandUrlService $urlService,
        ActionFactory $actionFactory,
        ResponseInterface $response,
        Config $config
        
    ) {
        $this->urlService    = $urlService;
        $this->actionFactory = $actionFactory;
        $this->response      = $response;
        $this->config        = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function match(RequestInterface $request)
    {
        $pathInfo = $request->getPathInfo();

        $result = $this->urlService->match($pathInfo);
        
        if ($result) {
            
            $params = $result->getData('params');
            
            if ($this->shouldRedirect($pathInfo) && $result->getModuleName() == 'brand') {
                return $this->processRedirect($request, $pathInfo);
            }

            $request
                ->setAlias(
                    \Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS,
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

    private function shouldRedirect(string $pathInfo): bool
    {
        $suffix = $this->config->getGeneralConfig()->getUrlSuffix();

        if (
            $suffix == '' && $this->endsWithSlash($pathInfo)
            || $suffix === '/' && !$this->endsWithSlash($pathInfo)
        ) {
            return true;
        }

        return false;
    }

    private function endsWithSlash(string $pathInfo): bool
    {
        return (substr($pathInfo, -1) == '/');
    }

    /**
     * Redirect to target URL
     *
     * @param RequestInterface $request
     * @param string $url
     * @return ActionInterface
     */
    private function processRedirect(RequestInterface $request, string $pathInfo)
    {
        $target = '';

        if ($this->endsWithSlash($pathInfo)) {
            $target = rtrim($pathInfo, '/');
        } else {
            $target = $pathInfo . '/';
        }

        $queryStringArray = $request->getParams();

        if (count($queryStringArray) > 0) {
            $queryString = http_build_query($queryStringArray);
            $target .= '?' . $queryString;
        }

        return $this->redirect($request, $target);
    }


    /**
     * Redirect to target URL
     *
     * @param RequestInterface $request
     * @param string $url
     * @return ActionInterface
     */
    private function redirect($request, $url)
    {
        $this->response->setRedirect($url);
        $request->setDispatched(true);

        return $this->actionFactory->create(Redirect::class);
    }
}
