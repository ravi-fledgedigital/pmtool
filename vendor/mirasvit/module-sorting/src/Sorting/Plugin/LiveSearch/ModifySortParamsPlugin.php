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


namespace Mirasvit\Sorting\Plugin\LiveSearch;

use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\SortQueryArgumentProcessor;
use Mirasvit\Sorting\Model\Config\Source\CriteriaSource;
use Magento\Framework\App\State as AppState;
use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Service\CriteriaApplierService;

class ModifySortParamsPlugin
{
    private $criteriaSource;

    private $appState;

    private $rankingFactorRepository;

    private $criterionRepository;

    private $criteriaApplierService;

    public function __construct(
        CriteriaSource $criteriaSource, 
        AppState $appState, 
        RankingFactorRepository $rankingFactorRepository, 
        CriterionRepository $criterionRepository,
        CriteriaApplierService $criteriaApplierService
    ) {
        $this->criteriaSource          = $criteriaSource;
        $this->appState                = $appState;
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->criterionRepository     = $criterionRepository;
        $this->criteriaApplierService  = $criteriaApplierService;
    }

    public function afterGetQueryArgumentValue(SortQueryArgumentProcessor $subject, array $result): array
    {
        $codes = array_keys($this->criteriaSource->toArray());

        foreach ($result as $idx => $option) {
            if (!in_array($option['attribute'], $codes)) {
                unset($result[$idx]);
            }
        }
        $newOrders = [];
        foreach ($result as $order) {
            $criterion = $this->criterionRepository->getByCode($order['attribute']);
            if ($criterion) {
                foreach ($this->criteriaApplierService->getFrames($criterion, $order['direction']) as $frame => $dir) {
                    $this->addOrder($newOrders, $frame, $dir);
                }
            }
        }

        if($this->isGlobalFactorActive()){
            array_unshift($newOrders, ['attribute' => 'sorting_global', 'direction' => 'DESC']);
        }
        return array_values($newOrders);
    }

    private function addOrder(array &$orderList, string $attr, string $direction): void
    {
        if (in_array($this->appState->getAreaCode(), ['webapi_rest', 'graphql'])) {
            $orderList[] = new \Magento\Framework\Api\SortOrder([
                'field'     => $attr,
                'direction' => $direction,
            ]);
        } else {
            $orderList[] = [
                'attribute' => $attr,
                'direction' => strtoupper($direction),
            ];
        }
    }

    private function isGlobalFactorActive()
    {
        return $this->rankingFactorRepository->getCollection()
            ->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true)
            ->addFieldToFilter(RankingFactorInterface::IS_GLOBAL, true)
            ->getSize();
    }
}