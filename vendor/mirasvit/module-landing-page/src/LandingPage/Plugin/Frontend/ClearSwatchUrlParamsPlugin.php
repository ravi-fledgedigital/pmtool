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

use Magento\Framework\App\RequestInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;

/**
 * @see \Magento\Swatches\Block\LayeredNavigation\RenderLayered::buildUrl()
 */
class ClearSwatchUrlParamsPlugin
{

    private $request;

    private $filterRepository;

    public function __construct(
        RequestInterface $request,
        FilterRepository $filterRepository
    ) {
        $this->request          = $request;
        $this->filterRepository = $filterRepository;
    }

    /**
     * @param object   $subject
     * @param string   $result
     *
     * @return string
     */
    public function afterBuildUrl($subject, $result)
    {
        if ((string)$this->request->getFullActionName() !== 'landing_landing_view') {
            return $result;
        }

        $urlData = parse_url($result);

        if (!isset($urlData['query'])) {
            return $result;
        }

        parse_str($urlData['query'], $queryParams);

        if (!isset($queryParams['landing'])) {
            return $result;
        }

        $pageId = intval($queryParams['landing']);

        $configuredFiltersCollection = $this->filterRepository->getByPageId($pageId);

        foreach ($configuredFiltersCollection as $filter) {
            if (isset($queryParams[$filter->getAttributeCode()])) {
                unset($queryParams[$filter->getAttributeCode()]);
            }
        }
        unset($queryParams['landing']);
        unset($queryParams['landing_search']);

        $newQueryString = http_build_query($queryParams);

        $resultUrl = $urlData['scheme']."://".$urlData['host'].'/'.$urlData['path'].'?'.$newQueryString;

        return $resultUrl;
    }
}
