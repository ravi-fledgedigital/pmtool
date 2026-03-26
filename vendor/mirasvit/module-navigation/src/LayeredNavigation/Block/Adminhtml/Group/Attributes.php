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


namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Group;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Model\Config\Source\AttributeSource;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class Attributes extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::group/attributes.phtml';
    
    protected $attributeSource;
    
    protected $groupRepository;
    
    public function __construct(
        AttributeSource $attributeSource,
        GroupRepository $groupRepository,
        Template\Context $context
    ) {
        $this->attributeSource = $attributeSource;
        $this->groupRepository = $groupRepository;
        
        parent::__construct($context);
    }

    public function getOptionsUpdateUrl(): string
    {
        return $this->getUrl('layered_navigation/group/options', ['_secure' => true]);
    }

    public function getAttributes(): array
    {
        return $this->attributeSource->toOptionArray();
    }

    public function getCurrentGroup(): ?GroupInterface
    {
        $id = (int)$this->getRequest()->getParam(GroupInterface::ID);
        
        if(!$id) {
            return null;
        }
        
        return $this->groupRepository->get($id);
    }

    public function getCurrentAttributeCode(): ?string
    {
        $group = $this->getCurrentGroup();
        
        return $group ? $group->getAttributeCode() : null;
    }

    public function getSelectedOptions(): ?array
    {
        $group = $this->getCurrentGroup();

        return $group ? $group->getAttributeValueIds() : null;
    }
}
