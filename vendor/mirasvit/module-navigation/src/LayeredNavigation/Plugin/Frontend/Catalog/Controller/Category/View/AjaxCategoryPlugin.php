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

namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Catalog\Controller\Category\View;

use Magento\Catalog\Model\Product\ProductList\Toolbar;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Model\Config\ConfigTrait;
use Mirasvit\LayeredNavigation\Service\AjaxResponseService;
use Magento\Framework\App\Response\Http as Response;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Mirasvit\Core\Service\CompatibilityService;

/**
 * @see \Magento\Catalog\Controller\Category\View::execute()
 */
class AjaxCategoryPlugin
{
    use ConfigTrait;

    private $ajaxResponseService;

    private $request;

    private $response;

    private $toolbarMemorizer;

    public function __construct(
        AjaxResponseService $ajaxResponseService,
        RequestInterface $request,
        Response $response,
        ToolbarMemorizer $toolbarMemorizer
    ) {
        $this->ajaxResponseService = $ajaxResponseService;
        $this->request             = $request;
        $this->response            = $response;
        $this->toolbarMemorizer    = $toolbarMemorizer;
    }

    /**
     * @param \Magento\Catalog\Controller\Category\View $subject
     * @param \Magento\Framework\View\Result\Page       $page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function afterExecute($subject, $page)
    {
        if ($this->isAllowed($this->request)) {

            // fix product list mode changing if "Remember Category Pagination" is enabled in magento2.4.7+
            if ($this->isRedirectOnToolbarAction()) {
                $this->response->_resetState();
            }
//            sleep(30);
            return $this->ajaxResponseService->getAjaxResponse($page);
        }

        return $page;
    }

    private function isRedirectOnToolbarAction(): bool
    {
        // apply for magento 2.4.7+
        $is247orHigher = version_compare(CompatibilityService::getVersion(), "2.4.7", ">=");
        
        $params = $this->request->getParams();

        return $is247orHigher && $this->response->isRedirect() && $this->toolbarMemorizer->isMemorizingAllowed() 
            && empty(array_intersect([
                Toolbar::ORDER_PARAM_NAME,
                Toolbar::DIRECTION_PARAM_NAME,
                Toolbar::MODE_PARAM_NAME,
                Toolbar::LIMIT_PARAM_NAME
            ], array_keys($params))) === false;
    }
}
