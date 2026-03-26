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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Service;

use Magento\Catalog\Model\Category;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Registry;

class BrandPageService
{
    private $registry;

    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function getBrandPage(): ?BrandPageInterface
    {
        return $this->registry->getBrandPage();
    }

    public function shouldDisplayProducts(): bool
    {
        $brandPage = $this->getBrandPage();

        if (!$brandPage) {
            return true; //fallback
        }

        $displayMode = $brandPage->getBrandDisplayMode();

        return $displayMode == Category::DM_PRODUCT || $displayMode == Category::DM_MIXED;
    }

    public function shouldDisplayCmsBlock(): bool
    {
        $brandPage = $this->getBrandPage();

        if (!$brandPage) {
            return false;
        }

        $displayMode = $brandPage->getBrandDisplayMode();

        return $displayMode == Category::DM_PAGE || $displayMode == Category::DM_MIXED;
    }
}
