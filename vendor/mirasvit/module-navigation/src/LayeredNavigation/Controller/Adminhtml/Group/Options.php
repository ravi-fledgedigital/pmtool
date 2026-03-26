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


namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;


use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Block\Adminhtml\Group\OptionLabel;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class Options extends Action
{
    private $jsonFactory;

    private $repository;

    private $groupRepository;

    private $optionLabel;

    public function __construct(
        AttributeRepository $repository,
        GroupRepository $groupRepository,
        OptionLabel $optionLabel,
        JsonFactory $jsonFactory,
        Context $context
    ) {
        $this->groupRepository = $groupRepository;
        $this->jsonFactory     = $jsonFactory;
        $this->repository      = $repository;
        $this->optionLabel     = $optionLabel;

        parent::__construct($context);
    }

    public function execute()
    {
        $code   = $this->getRequest()->getParam('code');
        $result = $this->jsonFactory->create();
        $html   = '';

        if (!$code) {
            return $result->setData(['success' => true, 'value' => $html]);
        }

        $attribute = $this->repository->get($code);

        foreach ($attribute->getOptions() as $option) {
            if (!trim((string)$option->getValue())) {
                continue;
            }

            $html .= $this->getOptionHtml($attribute, $option);
        }

        return $result->setData(['success' => true, 'value' => $html]);
    }

    private function getCurrentGroup(): ?GroupInterface
    {
        $groupId = (int)$this->getRequest()->getParam(GroupInterface::ID);

        return $groupId ? $this->groupRepository->get($groupId) : null;
    }

    private function getOptionHtml(ProductAttributeInterface $attribute, AttributeOptionInterface $option): string
    {
        $currentGroup = $this->getCurrentGroup();
        $key          = $attribute->getAttributeCode() . '-' . $option->getValue();
        $isChecked    = $currentGroup && in_array($option->getValue(), $currentGroup->getAttributeValueIds());

        return '<div class="admin__field admin__field-option mst-nav__option-wrapper">
                    <input '. ($isChecked ? 'checked' : '') . '
                            data-form-part="layered_navigation_group_form"
                            class="admin__control-checkbox"
                            type="checkbox"
                            name="' . 'attribute_values[' . $option->getValue() . ']' . '"
                            id="' . $key . '"/>
                    <label class="admin__field-label mst-nav__option-label" for="' . $key . '">'
                        . $this->optionLabel->getOptionLabelHtml($option)
                    . '</label>
                </div>';
    }
}
