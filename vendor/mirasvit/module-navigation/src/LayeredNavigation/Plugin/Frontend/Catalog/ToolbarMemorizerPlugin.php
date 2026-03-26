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

namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Catalog;

use Magento\Catalog\Model\Product\ProductList\Toolbar;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Model\Config\ConfigTrait;

/**
 * @see \Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer
 */
class ToolbarMemorizerPlugin
{
    use ConfigTrait;

    private $request;

    private $catalogSession;

    public function __construct(
        RequestInterface $request,
        CatalogSession $catalogSession
    ) {
        $this->request = $request;
        $this->catalogSession = $catalogSession;
    }

    public function afterGetMode(ToolbarMemorizer $subject, $result)
    {
        if ($this->shouldSkipSessionValue(Toolbar::MODE_PARAM_NAME)) {
            return $this->request->getParam(Toolbar::MODE_PARAM_NAME);
        }

        return $result;
    }

    public function afterGetOrder(ToolbarMemorizer $subject, $result)
    {
        if ($this->shouldSkipSessionValue(Toolbar::ORDER_PARAM_NAME)) {
            return $this->request->getParam(Toolbar::ORDER_PARAM_NAME);
        }

        return $result;
    }

    public function afterGetDirection(ToolbarMemorizer $subject, $result)
    {
        if ($this->shouldSkipSessionValue(Toolbar::DIRECTION_PARAM_NAME)) {
            return $this->request->getParam(Toolbar::DIRECTION_PARAM_NAME);
        }

        return $result;
    }

    public function afterGetLimit(ToolbarMemorizer $subject, $result)
    {
        if ($this->shouldSkipSessionValue(Toolbar::LIMIT_PARAM_NAME)) {
            return $this->request->getParam(Toolbar::LIMIT_PARAM_NAME);
        }

        return $result;
    }

    public function afterMemorizeParams(ToolbarMemorizer $subject, $result)
    {
        if (!$this->request->getParam('isAjax') || !$this->isAjaxEnabled()) {
            return $result;
        }

        $params = [
            Toolbar::MODE_PARAM_NAME,
            Toolbar::ORDER_PARAM_NAME,
            Toolbar::DIRECTION_PARAM_NAME,
            Toolbar::LIMIT_PARAM_NAME,
        ];

        foreach ($params as $param) {
            if (!$this->request->getParam($param)) {
                $this->catalogSession->unsetData($param);
            }
        }

        return $result;
    }

    private function shouldSkipSessionValue(string $paramName): bool
    {
        $isAjax = $this->request->getParam('isAjax');
        $ajaxEnabled = $this->isAjaxEnabled();
        $hasParam = $this->request->getParam($paramName);


        if (!$isAjax) {
            return false;
        }

        if (!$ajaxEnabled) {
            return false;
        }

        return !$hasParam;
    }
}
