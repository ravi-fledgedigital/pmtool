<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\ProductAlert\Rewrite\Block\Email;

/**
 * ProductAlert email back in stock grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Stock extends \Magento\ProductAlert\Block\Email\Stock
{
    /**
     * @var string
     */
    protected $_template = 'OnitsukaTiger_ProductAlert::email/stock.phtml';

    /**
     * Retrieve unsubscribe url for product
     *
     * @param int $productId
     * @return string
     */
    public function getProductUnsubscribeUrl($productId)
    {
        $params = $this->_getUrlParams();
        $params['product'] = $productId;
        return $this->getUrl('productalert/unsubscribe/email', $params);
    }

    /**
     * Retrieve unsubscribe url for all products
     *
     * @return string
     */
    public function getUnsubscribeUrl()
    {
        return $this->getUrl('productalert/unsubscribe/stockAll', $this->_getUrlParams());
    }
}
