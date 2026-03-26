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

namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Filter;

use Magento\Backend\App\Action;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as AttributeResource;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Service\FilterService;

class Add extends Action
{
    private $resultJsonFactory;
    private $attributeRepository;
    private $attributeResource;
    private $attributeConfigRepository;
    private $filterService;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        AttributeRepositoryInterface $attributeRepository,
        AttributeResource $attributeResource,
        AttributeConfigRepository $attributeConfigRepository,
        FilterService $filterService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeRepository = $attributeRepository;
        $this->attributeResource = $attributeResource;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->filterService = $filterService;
    }

    public function execute()
    {
        $newHorizontalPosition = 0;

        $result = $this->resultJsonFactory->create();

        $postData = $this->getRequest()->getPost('attribute');
        if (!$postData || !is_array($postData)) {
            return $result->setData(['success' => false, 'message' => 'No attribute data provided']);
        }

        try {
            $newSidebarPosition = $this->filterService->getMaxFilterableAttributePosition() + 1;

            if ($this->hasBothOrHorizontal($postData)) {
                $newHorizontalPosition = $this->filterService->getMaxHorizontalFilterPosition() + 1;
            }

            $newHorizontalPosition = $this->filterService->getMaxHorizontalFilterPosition() + 1;

            $attributeCodes = [];

            foreach ($postData as $attributeId => $data) {
                if (!isset($data['use']) || empty($data['use'])) {
                    continue;
                }

                /** @var AttributeInterface|EavAttribute $attribute */
                $attribute = $this->attributeRepository->get('catalog_product', (int)$attributeId);
                $attributeConfig = $this->attributeConfigRepository->getByAttributeCode($attribute->getAttributeCode(), false);

                if (!$attributeConfig) {
                    $attributeConfig = $this->attributeConfigRepository->create();
                    $attributeConfig->setAttributeId((int)$attribute->getId());
                    $attributeConfig->setAttributeCode($attribute->getAttributeCode());
                }

                $filterable = isset($data['filterable']) && in_array((int)$data['filterable'], [1, 2], true)
                    ? (int)$data['filterable']
                    : 1;

                $attribute->setIsFilterable($filterable);
                $location = $data['location'] ?? AttributeConfigInterface::POSITION_SIDEBAR;

                if (in_array($location, [AttributeConfigInterface::POSITION_SIDEBAR, AttributeConfigInterface::POSITION_BOTH], true)) {
                    $attribute->setPosition((int)$newSidebarPosition);
                    $newSidebarPosition++;
                }

                if (in_array($location, [AttributeConfigInterface::POSITION_HORIZONTAL, AttributeConfigInterface::POSITION_BOTH], true)) {
                    $attributeConfig->setHorizontalPosition((int)$newHorizontalPosition);
                    $newHorizontalPosition++;
                }

                $validPositions = [
                    AttributeConfigInterface::POSITION_SIDEBAR,
                    AttributeConfigInterface::POSITION_HORIZONTAL,
                    AttributeConfigInterface::POSITION_BOTH,
                ];

                if (in_array($location, $validPositions, true)) {
                    $attributeConfig->setPosition($location);
                }
                $this->attributeConfigRepository->save($attributeConfig);
                $this->attributeResource->save($attribute);
                $attributeCodes[] = $attribute->getFrontendLabel() ?: $attribute->getAttributeCode();
            }

            return $result->setData(['success' => true, 'message' => $this->getAddAttributeMessage($attributeCodes)]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getAddAttributeMessage(array $attributeCodes): string
    {
        if (count($attributeCodes) === 0) {
            return __('No attributes were added.')->render();
        }

        if (count($attributeCodes) === 1) {
            return __('Attribute "%1" was added.', $attributeCodes[0])->render();
        }

        return __('Attributes "%1" were added.', implode('", "', $attributeCodes))->render();
    }

    private function hasBothOrHorizontal(array $postData): bool
    {
        foreach ($postData as $item) {
            if (isset($item['location']) && in_array($item['location'], ['both', 'horizontal'], true)) {
                return true;
            }
        }
        return false;
    }
}
