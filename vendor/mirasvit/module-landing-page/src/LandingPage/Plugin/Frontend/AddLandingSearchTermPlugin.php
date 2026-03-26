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

use Magento\Catalog\Model\Layer;
use Magento\Framework\App\RequestInterface;

class AddLandingSearchTermPlugin
{
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function afterGetProductCollection(Layer $subject, $collection)
    {
        // Plugin to enable relevance sorting for Landing Pages with search terms
        if ($this->request->getFullActionName() === 'landing_landing_view') {
            $searchTerm = $this->request->getParam('landing_search');

            if ($searchTerm) {
                if ($this->isFulltextCollection($collection)) {
                    $this->configureSearchCollection($collection, $searchTerm);
                }
            }
        }

        return $collection;
    }

    private function isFulltextCollection($collection): bool
    {
        $class = get_class($collection);

        if (strpos($class, 'Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection') !== false) {
            return true;
        }

        if ($collection instanceof \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection) {
            return true;
        }

        $parentClass = get_parent_class($collection);
        while ($parentClass) {
            if ($parentClass === 'Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection') {
                return true;
            }
            $parentClass = get_parent_class($parentClass);
        }

        return false;
    }

    private function configureSearchCollection($collection, string $searchTerm): void
    {
        try {
            $queryTextProp = $this->findProperty($collection, 'queryText');
            if ($queryTextProp) {
                $queryTextProp->setAccessible(true);
                $currentQueryText = $queryTextProp->getValue($collection);

                if (empty($currentQueryText)) {
                    $queryTextProp->setValue($collection, trim($searchTerm));
                }
            }

            $searchRequestProp = $this->findProperty($collection, 'searchRequestName');
            if ($searchRequestProp) {
                $searchRequestProp->setAccessible(true);
                $searchRequestProp->setValue($collection, 'quick_search_container');
            }

        } catch (\Exception $e) {
            if (method_exists($collection, 'addSearchFilter')) {
                $collection->addSearchFilter($searchTerm);
            }
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
