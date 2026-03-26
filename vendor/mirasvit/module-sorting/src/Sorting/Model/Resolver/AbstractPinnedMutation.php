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

namespace Mirasvit\Sorting\Model\Resolver;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Mirasvit\Sorting\Service\PinnedProductService;

abstract class AbstractPinnedMutation implements ResolverInterface
{
    private const ACL_RESOURCE = 'Mirasvit_Sorting::sorting';

    protected $pinnedProductService;

    private   $authorization;

    public function __construct(
        PinnedProductService   $pinnedProductService,
        AuthorizationInterface $authorization
    ) {
        $this->pinnedProductService = $pinnedProductService;
        $this->authorization        = $authorization;
    }

    protected function validateAccess($context): void
    {
        if ($context->getUserType() !== 2 || $context->getUserId() === 0) {
            throw new GraphQlAuthorizationException(__('The current user is not authorized.'));
        }

        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            throw new GraphQlAuthorizationException(
                __('The current user is not authorized to manage pinned products.')
            );
        }
    }
}
