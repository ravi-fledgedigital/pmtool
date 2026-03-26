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

namespace Mirasvit\Sorting\Service\Autocomplete;

use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Service\Autocomplete\Resolver\FieldResolverInterface;

class CriterionFieldResolverComposite
{
    private array $resolvers;

    /**
     * @param array<FieldResolverInterface|empty> $resolvers
     */
    public function __construct(
        array $resolvers
    ) {
        $this->resolvers = $resolvers;
    }

    public function resolve(CriterionInterface $criterion): ?array
    {
        $conditionCluster = $criterion->getConditionCluster();
        if (null === $conditionCluster) {
            return null;
        }

        $frames = $conditionCluster->getFrames();
        if (null === $frames) {
            return null;
        }

        foreach ($frames as $frameIdx => $frame) {
            if (count($frame->getNodes()) >= 2) {
                return [
                    'order'     => 'sorting_criterion_' . $criterion->getId() . '_frame_' . $frameIdx,
                    'direction' => $frame->getDirection(),
                ];
            }

            $nodes = $frame->getNodes();
            foreach ($nodes as $node) {
                if (!isset($this->resolvers[$node->getSortBy()])) {
                    continue;
                }
                $resolver = $this->resolvers[$node->getSortBy()];

                return $resolver->resolveEsField($node);
            }
        }

        return null;
    }
}
