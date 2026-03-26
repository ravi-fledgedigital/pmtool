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

namespace Mirasvit\LandingPage\Ui\Page\Form\Control;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\PageRepository;

abstract class AbstractButton implements ButtonProviderInterface
{
    protected $context;

    protected $repository;

    public function __construct(
        Context        $context,
        PageRepository $repository
    ) {
        $this->context    = $context;
        $this->repository = $repository;
    }

    public function getId(): ?int
    {
        $id = $this->context->getRequest()->getParam(PageInterface::PAGE_ID);

        return $id ? (int)$id : null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

}
