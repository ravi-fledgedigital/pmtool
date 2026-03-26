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

namespace Mirasvit\Sorting\Service\Autocomplete\Resolver;

use Mirasvit\Sorting\Model\Criterion\ConditionNode;
use Mirasvit\Sorting\Service\Autocomplete\Resolver\FieldResolverInterface;

class RankingFactorFieldResolver implements FieldResolverInterface
{

    /**
     * @return array{
     *     order: string,
     *     direction: string
     * }
     */
    public function resolveEsField(ConditionNode $criterionNode): array
    {
        return [
            'order'     => 'sorting_factor_' . $criterionNode->getRankingFactor(),
            'direction' => $criterionNode->getDirection(),
        ];
    }
}
