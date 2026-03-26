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

namespace Mirasvit\Sorting\Plugin\SearchAutocomplete;

use Magento\Catalog\Model\Session;
use Mirasvit\SearchAutocomplete\Block\Injection as Subject;
use Magento\Framework\App\ObjectManager;

/**
 * @see Subject::getAvailableOrders()
 */
class SearchAutocompleteSorterPlugin
{
    private $catalogSession;

    public function __construct(
        Session $catalogSession
    ) {
        $this->catalogSession = $catalogSession;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetAvailableOrders(Subject $subject): void
    {
        if (class_exists('Mirasvit\SearchAutocomplete\Model\ConfigProvider')) {
            $config = ObjectManager::getInstance()->create('Mirasvit\SearchAutocomplete\Model\ConfigProvider');
            $this->catalogSession->setPreventConfiguredSorting($config->isFastModeEnabled());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAvailableOrders(Subject $subject, array $result): array
    {
        $this->catalogSession->setPreventConfiguredSorting(false);

        return $result;
    }

}
