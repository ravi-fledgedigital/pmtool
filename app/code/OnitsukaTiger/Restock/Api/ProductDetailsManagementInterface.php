<?php
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Api;

interface ProductDetailsManagementInterface
{

    /**
     * POST for ProductDetails api
     * @param string $productId
     * @return string
     */
    public function getProductDetails($productId);
}
