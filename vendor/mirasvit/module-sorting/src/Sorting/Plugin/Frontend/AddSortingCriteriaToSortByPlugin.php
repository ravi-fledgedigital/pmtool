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

use Magento\Catalog\Model\Session;
use Magento\Catalog\Model\Config as Subject;
use Magento\Framework\App\ObjectManager;
use Mirasvit\Sorting\Model\Config\Source\CriteriaSource;
use Mirasvit\Sorting\Service\AutocompleteSortingService;

/**
 * Adds Improved Sorting criteria to default "sort by" options.
 * @see Subject::getAttributeUsedForSortByArray
 */
class AddSortingCriteriaToSortByPlugin
{
    private $criteriaSource;

    private $catalogSession;

    private $autocompleteSortingService;

    public function __construct(
        CriteriaSource             $criteriaSource,
        Session                    $catalogSession,
        AutocompleteSortingService $autocompleteSortingService
    ) {
        $this->criteriaSource = $criteriaSource;
        $this->catalogSession = $catalogSession;
        $this->autocompleteSortingService = $autocompleteSortingService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAttributeUsedForSortByArray(Subject $subject, array $result = []): array
    {
        if ($this->catalogSession->getPreventConfiguredSorting()) {
            $autocompleteSortingOptions = $this->autocompleteSortingService->getAutocompleteSortingOptions();

            if (null === $autocompleteSortingOptions) {
                return $result;
            }

            return $autocompleteSortingOptions;
        }

        $options = $this->criteriaSource->getConfiguredSortingOptions();

        if (count($options) === 0) {
            $options = $result;
        }

        if (isset($options['relevance'])) {
            unset($options['relevance']);
        }

        return $options;
    }
}
