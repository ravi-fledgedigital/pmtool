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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model\Config\Source;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CategorySource implements OptionSourceInterface
{
    private $collectionFactory;

    private $categoryRepository;

    public function __construct(
        CollectionFactory           $collectionFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->collectionFactory  = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $result     = [];
        $categories = $this->collectionFactory->create();

        foreach ($categories as $category) {
            $category = $this->categoryRepository->get($category->getEntityId());

            if ($category->getId() == 1) {
                $result[] = [
                    'label' => 'All Products',
                    'value' => 0,
                ];
                continue;
            }

            $result[] = [
                'label' => $category->getName(),
                'value' => $category->getId(),
            ];
        }

        return $result;
    }

}
