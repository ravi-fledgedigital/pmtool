<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Plugin\Catalog\Helper\Product\View;

use Amasty\CustomTabs\Model\ConfigProvider;
use Magento\Catalog\Helper\Product\View as ProductViewHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\Page as ResultPage;

class AddBodyClass
{
    public const PRODUCT_ACTION_NAME = 'catalog_product_view';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Http
     */
    private $request;

    public function __construct(
        ConfigProvider $configProvider,
        Http $request
    ) {
        $this->configProvider = $configProvider;
        $this->request = $request;
    }

    /**
     * @param ProductViewHelper $subject
     * @param ProductViewHelper $result
     * @param ResultPage $resultPage
     * @param Product $product
     * @param DataObject|null $params
     */
    public function afterInitProductLayout(
        ProductViewHelper $subject,
        $result,
        ResultPage $resultPage,
        $product,
        $params = null
    ): ProductViewHelper {
        if ($this->configProvider->isEnabled() && $this->request->getFullActionName() == self::PRODUCT_ACTION_NAME) {
            $resultPage->getConfig()->addBodyClass('am-tabs-view');
        }

        return $result;
    }
}
