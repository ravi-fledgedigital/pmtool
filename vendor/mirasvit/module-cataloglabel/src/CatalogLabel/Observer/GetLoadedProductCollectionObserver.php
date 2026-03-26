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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\CatalogLabel\Api\Data\IndexInterface;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display as DisplayResource;

class GetLoadedProductCollectionObserver implements ObserverInterface
{
    private $storeManager;

    private $resource;

    private $customerSession;

    public function __construct(
        StoreManagerInterface $storeManager,
        DisplayResource $resource,
        Session $customerSession
    ) {
        $this->storeManager    = $storeManager;
        $this->resource        = $resource;
        $this->customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productCollection = $observer->getEvent()->getCollection();

        $customerGroupId = $this->customerSession->getCustomerGroupId() ?? 0;

        $joinExpr = new \Zend_Db_Expr(
            'e.entity_id = product_label.product_id '
            . ' AND FIND_IN_SET(' . $customerGroupId . ', product_label.customer_groups) '
            . ' AND product_label.store_id = ' . $this->storeManager->getStore()->getId()
        );

        if (!$productCollection->isLoaded()) {
            $productCollection->getSelect()
                ->joinLeft(
                    ['product_label' => $this->resource->getTable(IndexInterface::TABLE_NAME)],
                    $joinExpr,
                    [
                        'mst_product_label_id'    => new \Zend_Db_Expr("IFNULL(GROUP_CONCAT(product_label.label_id), 0)"),
                        'mst_product_display_ids' => new \Zend_Db_Expr("IFNULL(GROUP_CONCAT(CONCAT_WS('-', product_label.sort_order, product_label.display_ids) SEPARATOR '|'), 0)")
                    ]
                )
                ->group('e.entity_id');
        }

        return $this;
    }
}
