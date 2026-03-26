<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Helper\Product;

use Magento\Catalog\Helper\Product\View as ProductViewHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\Page;

class View
{
    const PRODUCT_LAYOUT_HANDLE = 'product_view';

    protected $request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ){
        $this->request = $request;
    }

    public function beforeInitProductLayout(
        ProductViewHelper $subject,
        $resultPage,
        $product,
        $params
    ) {
        if($this->request->getControllerModule() =='Magento_Catalog'){
            if ($resultPage instanceof ResultPage) {
                $resultPage->addHandle([static::PRODUCT_LAYOUT_HANDLE]);
            }
        }
        return [
            $resultPage,
            $product,
            $params
        ];
    }
}
