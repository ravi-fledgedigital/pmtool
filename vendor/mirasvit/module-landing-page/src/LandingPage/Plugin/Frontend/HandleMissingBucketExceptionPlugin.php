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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Plugin\Frontend;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\StateException;

class HandleMissingBucketExceptionPlugin
{
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function aroundGetFacetedData(Collection $subject, callable $proceed, string $field)
    {
        if ($this->request->getFullActionName() !== 'landing_landing_view') {
            return $proceed($field);
        }

        $searchTerm = $this->request->getParam('landing_search');
        if (!$searchTerm) {
            return $proceed($field);
        }

        try {
            return $proceed($field);
        } catch (StateException $e) {
            // If bucket doesn't exist when using quick_search_container for relevance sorting,
            // return empty array to prevent layered navigation from breaking
            if (strpos($e->getMessage(), "bucket doesn't exist") !== false ||
                strpos($e->getMessage(), "doesn't exist") !== false) {
                return [];
            }
            throw $e;
        }
    }
}
