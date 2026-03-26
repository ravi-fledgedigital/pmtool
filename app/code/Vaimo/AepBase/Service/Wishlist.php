<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist as CustomerWishlist;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Model\ResourceModel\Wishlist as ResourceModel;

class Wishlist
{
    public const MAX_CHARACTER_LENGTH = 255; // limited to varchar(255) column length

    private CustomerRepositoryInterface $customerRepository;
    private ResourceModel $resourceModel;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ResourceModel $resourceModel
    ) {
        $this->customerRepository = $customerRepository;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function updateCustomer(CustomerWishlist $wishlist): void
    {
        $customer = $this->customerRepository->getById($wishlist->getCustomerId());
        $wishlistItems = $this->resourceModel->getWishlistItems((int) $wishlist->getId());

        $currentWishlistData = $customer->getExtensionAttributes()->getAepWhishlistProducts();
        $newWishlistData = \implode(ConfigInterface::SKU_DELIMITER, $this->getMostRecentSkus($wishlistItems));

        if ($currentWishlistData === $newWishlistData) {
            return;
        }


        $customer->getExtensionAttributes()->setAepWhishlistProducts($newWishlistData);

        // set wishlist modified datetime;
        if($newWishlistData){
            $customer->getExtensionAttributes()->setAepWishlistModifiedDatetime(date('Y-m-d H:i:s'));
        }

        $this->customerRepository->save($customer);
    }

    /**
     * Will count chars and return only most recent skus which will fit into varchar(255)
     * @param string[][] $wishlistItems
     * @return string[]
     */
    private function getMostRecentSkus(array $wishlistItems): array
    {
        $result = [];
        $totalCharacters = 0;

        foreach ($wishlistItems as $itemData) {
            $sku = $itemData['child_sku'] ?: $itemData['sku'];
            $sku .= ConfigInterface::STORE_CODE_DELIMITER . $itemData['store_code'];

            $totalCharacters += \strlen($sku);

            if ($totalCharacters > self::MAX_CHARACTER_LENGTH) {
                break;
            }

            $totalCharacters++; // adding delimiter
            $result[] = $sku;
        }

        return $result;
    }
}
