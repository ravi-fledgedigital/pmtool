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

namespace Mirasvit\LayeredNavigation\Model\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;

trait ConfigTrait
{
    /**
     * @return int
     */
    public static function isMultiselectEnabled(string $attributeCode)
    {
        return self::getConfig()->isMultiselectEnabled($attributeCode);
    }

    /**
     * @param mixed $store
     *
     * @return bool
     */
    public static function isShowNestedCategories($store = null)
    {
        return self::getConfig()->isShowNestedCategories();
    }

    /**
     * @return \Mirasvit\LayeredNavigation\Model\ConfigProvider
     */
    protected static function getConfig()
    {
        return ObjectManager::getInstance()->get(\Mirasvit\LayeredNavigation\Model\ConfigProvider::class);
    }

    /**
     * @return bool
     */
    public function isAjaxEnabled()
    {
        return self::getConfig()->isAjaxEnabled();
    }

    /**
     * @return string
     */
    public function getApplyingMode()
    {
        return self::getConfig()->getApplyingMode();
    }

    /**
     * Is allowed to process request.
     *
     * @param \Magento\Framework\App\Request\Http|RequestInterface $request
     *
     * @return bool
     */
    protected function isAllowed($request)
    {
        if ($request->getFullActionName() === 'search_landing_page_view') {
            return false;
        }

        return $request->isAjax()
            && $this->isAjaxEnabled()
            && !$this->isExternalRequest($request)
            && !$this->isLandingPageRedirect($request);
    }

    /**
     * Is request triggered by external modules.
     *
     * @param \Magento\Framework\App\Request\Http|\Magento\Framework\App\RequestInterface $request
     *
     * @return bool
     */
    protected function isExternalRequest($request)
    {
        $externalParams = ['ajaxscroll', 'is_scroll', 'scrollAjax'];
        $params         = $request->getParams();

        foreach ($externalParams as $param) {
            if (array_key_exists($param, $params)) {
                return true;
            }
        }

        return false;
    }

    public function isLandingPageRedirect(RequestInterface $request): bool
    {
        $redirect = ObjectManager::getInstance()->get(\Magento\Framework\App\Response\RedirectInterface::class);

        $params = $request->getParams();

        if (
            $request->getFullActionName() == 'landing_landing_view'
            && isset($params['landing'])
            && class_exists('Mirasvit\LandingPage\Service\RedirectService')
        ) {

            $redirectService = ObjectManager::getInstance()->get(\Mirasvit\LandingPage\Service\RedirectService::class);

            return $redirectService->isRedirected(intval($params['landing']), $redirect->getRedirectUrl());
        }

        return false;
    }
}
