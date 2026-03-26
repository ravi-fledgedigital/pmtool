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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection;

class PreserveSearchTermPlugin
{
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function aroundGetExtendedFacetedData(
        Collection $subject,
        callable $proceed,
        string $field,
        bool $exclude = false,
        ?int $allowedValue = null
    ): array {
        if ($this->request->getFullActionName() !== 'landing_landing_view' || !$exclude) {
            return $proceed($field, $exclude, $allowedValue);
        }

        $searchTerm = $this->request->getParam('landing_search');
        if (!$searchTerm) {
            return $proceed($field, $exclude, $allowedValue);
        }

        try {
            $queryTextProp = $this->findProperty($subject, 'queryText');
            if ($queryTextProp) {
                $queryTextProp->setAccessible(true);
                $currentQueryText = $queryTextProp->getValue($subject);

                if (empty($currentQueryText)) {
                    $queryTextProp->setValue($subject, trim($searchTerm));
                }
            }

            $this->prepareSearchTermFilter($subject);
        } catch (\Exception $e) {
            // Continue if reflection fails
        }

        return $proceed($field, $exclude, $allowedValue);
    }

    private function prepareSearchTermFilter(Collection $subject): void
    {
        try {
            $queryTextProp = $this->findProperty($subject, 'queryText');
            $filterBuilderProp = $this->findProperty($subject, 'filterBuilder');
            $searchCriteriaBuilderProp = $this->findProperty($subject, 'searchCriteriaBuilder');

            if (!$queryTextProp || !$filterBuilderProp || !$searchCriteriaBuilderProp) {
                return;
            }

            $queryTextProp->setAccessible(true);
            $filterBuilderProp->setAccessible(true);
            $searchCriteriaBuilderProp->setAccessible(true);

            $queryText = $queryTextProp->getValue($subject);
            if (!$queryText) {
                return;
            }

            $filterBuilder = $filterBuilderProp->getValue($subject);
            $searchCriteriaBuilder = $searchCriteriaBuilderProp->getValue($subject);

            if ($filterBuilder && $searchCriteriaBuilder) {
                $filterBuilder->setField('search_term');
                $filterBuilder->setValue($queryText);
                $searchCriteriaBuilder->addFilter($filterBuilder->create());
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    private function findProperty($object, string $propertyName): ?\ReflectionProperty
    {
        $class = get_class($object);

        while ($class) {
            try {
                $reflection = new \ReflectionClass($class);

                if ($reflection->hasProperty($propertyName)) {
                    return $reflection->getProperty($propertyName);
                }

                $class = $reflection->getParentClass();
                $class = $class ? $class->getName() : false;
            } catch (\ReflectionException $e) {
                break;
            }
        }

        return null;
    }
}
