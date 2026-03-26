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


use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;

class Save extends Group
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam(GroupInterface::ID);

        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->getRequest()->getParams()) {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addError('No data to save.');

            return $resultRedirect;
        }

        $model = $this->initModel();

        if (!$model->getId() && $id) {
            $this->messageManager->addError((string)__('This Grouped Options no longer exist.'));

            return $resultRedirect->setPath('*/*/');
        }

        $data = $this->prepareData($model, $this->getRequest()->getParams());

        if (!$data) {
            return $id
                ? $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $id])
                : $resultRedirect->setPath('*/*/edit');
        }

        $groups = $this->repository
            ->getCollection()
            ->addFieldToFilter(GroupInterface::CODE, ['eq' => $data[GroupInterface::CODE]]);

        if ($id) {
            $groups->addFieldToFilter(GroupInterface::ID, ['neq' => $id]);
        }

        if ($groups->getSize()) {
            $this->messageManager->addError((string)__('Grouped Options with the code %1 already exists.', $data[GroupInterface::CODE]));

            return $id
                ? $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $id])
                : $resultRedirect->setPath('*/*/edit');
        }

        $model->setIsActive((bool)$data[GroupInterface::IS_ACTIVE])
            ->setPosition((int)$data[GroupInterface::POSITION])
            ->setTitle((array)$data[GroupInterface::TITLE])
            ->setSwatchType((int)$data[GroupInterface::SWATCH_TYPE])
            ->setSwatchValue(isset($data[GroupInterface::SWATCH_VALUE]) ? (string)$data[GroupInterface::SWATCH_VALUE] : null)
            ->setCode((string)$data[GroupInterface::CODE])
            ->setAttributeCode((string)$data[GroupInterface::ATTRIBUTE_CODE])
            ->setattributeValueIds((array)$data[GroupInterface::ATTRIBUTE_VALUE_IDS]);

        try {
            $this->repository->save($model);
            $this->messageManager->addSuccess((string)__('Grouped Options were successfully saved'));

            return $this->getRequest()->getParam('back')
                ? $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $model->getId()])
                : $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $id]);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prepareData(GroupInterface $model, array $data): ?array
    {
        if (
            (is_numeric($data['attribute_code']) && $data['attribute_code'] == 0)
            || !isset($data['attribute_values'])
        ) {
            $this->messageManager->addError((string)__("Please select attribute and options"));

            return null;
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $data[GroupInterface::CODE] ?? '')) {
            $this->messageManager->addError((string)__(
                "Invalid Group Code. Use only letters (a-z, A-Z), numbers (0-9), underscore (_) or hyphen (-). Must start with a letter."
            ));

            return null;
        }

        $attributeValues = array_filter($data['attribute_values']);
        $attributeValues = array_keys($attributeValues);

        $data[GroupInterface::ATTRIBUTE_VALUE_IDS] = $attributeValues;
        unset($data['attribute_values']);

        switch ($data[GroupInterface::SWATCH_TYPE]) {
            case GroupInterface::SWATCH_TYPE_COLOR:
                $data[GroupInterface::SWATCH_VALUE] = $data['colors_filter'];
                unset($data['colors_filter']);

                break;
            case GroupInterface::SWATCH_TYPE_IMAGE:
                $fileName = $model->getSwatchValue();

                if (isset($data['file'][0]['name'])) {
                    $fileName = $data['file'][0]['name'];
                } elseif (
                    (!$fileName)
                    || (strpos($fileName, '#') === 0 && strlen($fileName) === 7)
                ) {
                    $this->messageManager->addError((string)__("Please upload the swatch image"));

                    return null;
                }

                $data[GroupInterface::SWATCH_VALUE] = $fileName;
                unset($data['file']);

                break;
            default:
                $data[GroupInterface::SWATCH_VALUE] = null;
        }

        $labels = [];

        foreach ($data[GroupInterface::TITLE] as $key => $value) {
            $value = trim($value);

            if ($key == 0 && !$value) {
                $this->messageManager->addError((string)__("Label for the Admin area can't be empty"));

                return null;
            }

            if ($value) {
                $labels[] = [
                    'store_id' => $key,
                    'label'    => $value
                ];
            }
        }

        $data[GroupInterface::TITLE] = $labels;

        return $data;
    }
}
