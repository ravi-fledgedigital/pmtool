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

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class BlockSource implements OptionSourceInterface
{
    private $blockRepository;

    private $searchCriteriaBuilder;

    public function __construct(
        BlockRepositoryInterface $blockRepository,
        SearchCriteriaBuilder    $searchCriteriaBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->blockRepository       = $blockRepository;
    }

    public function toOptionArray(): array
    {
        $result         = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $cmsBlocks      = $this->blockRepository->getList($searchCriteria)->getItems();

        $result[] = [
            'label' => 'Select block',
            'value' => '0',
        ];

        foreach ($cmsBlocks as $block) {
            $result[] = [
                'label' => $block->getTitle(),
                'value' => $block->getId(),
            ];
        }

        return $result;
    }

}
