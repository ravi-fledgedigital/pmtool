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

use Magento\Framework\Event\ObserverInterface;
use Mirasvit\CatalogLabel\Repository\NewProductsRepository;
use Mirasvit\CatalogLabel\Api\Data\NewProductsInterface;
use Mirasvit\CatalogLabel\Model\ConfigProvider;

class NewProductsObserver implements ObserverInterface
{
    private $newProducts;

    private $newProductsRepository;

    private $config;

    public function __construct(
        NewProductsRepository $newProductsRepository,
        NewProductsInterface $newProducts,
        ConfigProvider $config
    ) {
        $this->newProductsRepository = $newProductsRepository;
        $this->newProducts           = $newProducts;
        $this->config                = $config;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (
            $this->config->isFlushCacheEnabled(null)
            && ($controller = $observer->getController())
            && is_object($controller)
        ) {
                $path = $controller->getRequest()->getOriginalPathInfo();

            if ($this->isNewProduct($path)) {
                $product   = $observer->getProduct();
                $productId = (int)$product->getId();

                $this->newProductsRepository->save($this->newProducts->setProductId($productId));
            }
        }
    }

    private function isNewProduct(string $path): bool
    {
        if (strpos($path, '/id/') === false) {
            return true;
        }

        return false;
    }
}
