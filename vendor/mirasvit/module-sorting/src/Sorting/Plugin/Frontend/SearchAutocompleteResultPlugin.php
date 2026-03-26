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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Plugin\Frontend;

use Mirasvit\SearchAutocomplete\Model\Result as Subject;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Magento\Framework\App\ObjectManager;

/**
 * @see Subject::getDirection()
 */
class SearchAutocompleteResultPlugin
{
    private $criterionRepository;

    public function __construct(
        CriterionRepository $criterionRepository
    ) {
        $this->criterionRepository = $criterionRepository;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetDirection(Subject $subject, string $result): string
    {
        if (!class_exists('Mirasvit\SearchAutocomplete\Model\ConfigProvider')) {
            return $result;
        }

        $config = ObjectManager::getInstance()->create('Mirasvit\SearchAutocomplete\Model\ConfigProvider');

        if ($config->isFastModeEnabled() || $subject->getIsActiveDirection()) {
            return $result;
        }

        $orderCode = $subject->getOrderBy();

        $criterion = $this->criterionRepository->getByCode($orderCode);

        if ($criterion) {
            $frames = $criterion->getConditionCluster()->getFrames();
            if (count($frames)) {
                $result = end($frames)->getDirection();
            }
        }

        return $result;
    }
}
