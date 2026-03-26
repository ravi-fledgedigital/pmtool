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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Label;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Label;
use Mirasvit\CatalogLabel\Model\Indexer;
use Mirasvit\Core\Service\SerializeService;

class Save extends Label
{
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if ($origData = $this->getRequest()->getParams()) {
            $model = $this->getModel();
            $data  = $this->prepareData($origData, $model);

            $model->addData($data);

            if (!$model->getData('label_id')) {
                $model->unsetData('label_id');
            }

            try {
                $this->labelRepository->save($model);

                $this->messageManager->addSuccess((string)__('Label was successfully saved'));
                $this->backendSession->setFormData(false);

                $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);

                if ($idxr->isScheduled()) {
                    $this->messageManager->addNotice((string)__(
                        'The "%1" index is set to "Update by Schedule". To apply label immediately press the "Apply Label" button',
                        $idxr->getTitle()
                    ));
                }

                $this->_redirect('*/*/edit', ['id' => $model->getId()]);

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->backendSession->setFormData($origData);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('cataloglabel/label/edit', ['id' => $this->getRequest()->getParam('id')]);

                    return;
                }
                $this->_redirect('*/*/');

                return;
            }
        }
        $this->messageManager->addError((string)__('Unable to find label to save'));
        $this->_redirect('*/*/');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function prepareData(array $data, LabelInterface $model): array
    {
        $data['label'] = $data['general'];

        if ($data['label']['type'] == 'rule' && isset($data['label']['attribute_id'])) {
            unset($data['label']['attribute_id']);
        }

        if (!isset($data['label']['placeholder_id'])) {
            $data['label']['placeholder_id'] = 0;
        }

        if (isset($data['rule'])) {
            if (isset($data['rule']['conditions'])) {
                $rule = $model->getRule();

                $rule->loadPost(['conditions' => $data['rule']['conditions']]);

                $conditions = $rule->getConditions()->asArray();

                $data['label'][LabelInterface::CONDITIONS_SERIALIZED] = SerializeService::encode($conditions);
            } else {
                $data['label'][LabelInterface::CONDITIONS_SERIALIZED] = SerializeService::encode([]);
            }
        }

        if (isset($data['display'])) {
            $data['label']['display'] = $data['display'];
        }

        if (isset($data['label']['store_ids'])) {
            $data['label']['store_ids'] = implode(',', $data['label']['store_ids']);
        } else {
            $data['label']['store_ids'] = 0;
        }

        if (isset($data['label']['customer_group_ids'])) {
            $data['label']['customer_group_ids'] = implode(',', $data['label']['customer_group_ids']);
        } else {
            $data['label']['customer_group_ids'] = 0;
        }

        $data = $data['label'];

        if (!empty($data['active_from'])) {
            $fromDateFrom        = str_replace('/', '-', $data['active_from']);
            $formattedDateFrom   = $this->timezone->date($fromDateFrom)->format(Mysql::DATETIME_FORMAT);
            $data['active_from'] = $formattedDateFrom;
        } else {
            $data['active_from'] = null;
        }

        if (!empty($data['active_to'])) {
            $fromDateTo        = str_replace('/', '-', $data['active_to']);
            $formattedDateTo   = $this->timezone->date($fromDateTo)->format(Mysql::DATETIME_FORMAT);
            $data['active_to'] = $formattedDateTo;
        } else {
            $data['active_to'] = null;
        }

        return $data;
    }
}
