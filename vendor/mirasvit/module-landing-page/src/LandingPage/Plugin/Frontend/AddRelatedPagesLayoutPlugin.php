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
use Magento\Framework\View\Model\Layout\Merge;
use Mirasvit\LandingPage\Service\RelatedPagesLayoutService;

class AddRelatedPagesLayoutPlugin
{
    private $request;

    private $layoutService;

    public function __construct(
        RequestInterface          $request,
        RelatedPagesLayoutService $layoutService
    ) {
        $this->request       = $request;
        $this->layoutService = $layoutService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(Merge $subject, Merge $result): Merge
    {
        $xml = $this->layoutService->getLayoutXml($this->request->getFullActionName());

        if ($xml !== null) {
            $subject->addUpdate($xml);
        }

        return $result;
    }
}
