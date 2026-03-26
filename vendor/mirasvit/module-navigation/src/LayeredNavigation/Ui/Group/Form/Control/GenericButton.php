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


namespace Mirasvit\LayeredNavigation\Ui\Group\Form\Control;


use Magento\Backend\Block\Widget\Context;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class GenericButton
{
    protected $repository;
    
    protected $context;
    
    public function __construct(
        GroupRepository $repository,
        Context $context
    ) {
        $this->repository = $repository;
        $this->context    = $context;
    }
    
    public function getId(): ?int
    {
        return $this->context->getRequest()->getParam(GroupInterface::ID)
            ? (int)$this->context->getRequest()->getParam(GroupInterface::ID)
            : null;
    }

    public function getModel(): ?GroupInterface
    {
        return $this->repository->get($this->getId());
    }

    public function getUrl(?string $route = null, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route ?: '', $params);
    }
}