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
use Magento\Backend\App\Action\Context;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Message\ManagerInterface;

class Remove extends Action
{
    private JsonFactory $resultJsonFactory;
    private AttributeRepositoryInterface $attributeRepository;
    private AttributeConfigRepository $attributeConfigRepository;
    private ManagerInterface $messsage;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttributeRepositoryInterface $attributeRepository,
        AttributeConfigRepository $attributeConfigRepository,
        ManagerInterface $messsage
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeRepository = $attributeRepository;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->messsage = $messsage;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $attributeId = (int)$this->getRequest()->getParam('attribute_id');
        $from = $this->getRequest()->getParam('from'); // 'sidebar' or 'horizontal'

        if (!$attributeId || !$from) {
            return $result->setData(['success' => false, 'message' => 'Missing required parameters']);
        }

        try {
            /** @var Attribute $attribute */
            $attribute = $this->attributeRepository->get('catalog_product', $attributeId);

            if (!$attribute->getId()) {
                throw new LocalizedException(__('Attribute not found'));
            }
            $attributeConfig = $this->attributeConfigRepository->getByAttributeId($attributeId, false);

            if (!$attributeConfig || !$attributeConfig->getId()) {
                return $this->disableFilter($attribute, 'Attribute config not found.');
            }

            $currentPosition = $attributeConfig->getPosition();

            if ($currentPosition === $from) {
                return $this->disableFilter($attribute);
            }

            if ($currentPosition === AttributeConfigInterface::POSITION_BOTH) {
                $newPosition = $from === AttributeConfigInterface::POSITION_SIDEBAR
                    ? AttributeConfigInterface::POSITION_HORIZONTAL
                    : AttributeConfigInterface::POSITION_SIDEBAR;

                $attributeConfig->setPosition($newPosition);
                $this->attributeConfigRepository->save($attributeConfig);

                return $result->setData([
                    'success' => true,
                    'removed_completely' => false,
                    'message' => __(
                        'Attribute "%1" has been removed from the "%2" list of filterable attributes.',
                        $attribute->getFrontendLabel() ?: $attribute->getAttributeCode(),
                        $from
                    )
                ]);
            }

            return $this->disableFilter($attribute, 'Config position is not valid.');
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function disableFilter(Attribute $attribute, string $message = ''): Json
    {
        $attribute->setIsFilterable(0);
        $this->attributeRepository->save($attribute);

        $msg = $message ?: __(
            'Attribute "%1" has been removed from the list of filterable attributes.',
            $attribute->getFrontendLabel() ?: $attribute->getAttributeCode()
        );

        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'removed_completely' => true,
            'message' => $msg,
        ]);
    }
}
