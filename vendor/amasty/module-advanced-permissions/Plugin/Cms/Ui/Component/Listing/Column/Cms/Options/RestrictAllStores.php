<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Cms\Ui\Component\Listing\Column\Cms\Options;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Ui\Component\Listing\Column\Cms\Options;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class RestrictAllStores
{
    public const ALL_STORE_VIEWS_KEY = 0;

    public function __construct(
        private readonly Data $helper,
        private readonly RequestInterface $request,
        private readonly PageRepository $pageRepository,
        private readonly BlockRepository $blockRepository
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToOptionArray(
        Options $subject,
        array $result
    ): array {
        if ($this->request->getActionName() === 'index'
            || !($rule = $this->helper->currentRule())
        ) {
            return $result;
        }

        $allowedStores = $rule->getScopeStoreviews();
        if (!$allowedStores) {
            return $result;
        }

        $cmsEntity = false;

        try {
            if ($entityId = $this->request->getParam('page_id')) {
                $cmsEntity = $this->pageRepository->getById($entityId);
            } elseif ($entityId = $this->request->getParam('block_id')) {
                $cmsEntity = $this->blockRepository->getById($entityId);
            }
        } catch (NoSuchEntityException $e) {
            null;
        }

        if (!$cmsEntity
            || ($cmsEntity->getStores()
                && !in_array(self::ALL_STORE_VIEWS_KEY, $cmsEntity->getStores())
            )
        ) {
            unset($result[self::ALL_STORE_VIEWS_KEY]);
        }

        return $result;
    }
}
