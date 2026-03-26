<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Plugin\Cms\Model;

use Magento\VersionsCms\Helper\Hierarchy;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageRepository;

/**
 * Convert 'assign to root' flag to boolean
 */
class PageRepositoryPlugin
{
    /**
     * @var Hierarchy
     */
    private $cmsHierarchy;

    /**
     * @param Hierarchy $cmsHierarchy
     */
    public function __construct(
        Hierarchy $cmsHierarchy
    ) {
        $this->cmsHierarchy = $cmsHierarchy;
    }

    /**
     * Converts 'assign to root' flag to bool boolean
     *
     * @param PageRepository $subject
     * @param PageInterface $page
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        PageRepository $subject,
        PageInterface $page
    ): array {
        if (!$this->cmsHierarchy->isEnabled()) {
            return [$page];
        }
        if ($page->getAssignToRoot()) {
            $page->setAssignToRoot(json_decode($page->getAssignToRoot()));
        }

        return [$page];
    }
}
