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

namespace Mirasvit\LandingPage\Controller\Adminhtml\Page;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Mirasvit\LandingPage\Api\Data\FilterInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Controller\Adminhtml\PageAbstract;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\DB\Adapter\DuplicateException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends PageAbstract
{
    private $filterRepository;

    private $attribute;

    public function __construct(
        ProductAttributeRepositoryInterface $attribute,
        FilterRepository                    $filterRepository,
        PageRepository                      $pageRepository,
        Registry                            $registry,
        ForwardFactory                      $resultForwardFactory,
        Context                             $context
    ) {
        $this->attribute        = $attribute;
        $this->filterRepository = $filterRepository;
        parent::__construct($pageRepository, $registry, $resultForwardFactory, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $model          = $this->initModel();

        if ($this->getRequest()->getParam(PageInterface::PAGE_ID) && !$model->getId()) {
            $this->messageManager->addErrorMessage((string)__('The page was removed by another user or does not exist.'));

            return $resultRedirect->setPath('*/*/');
        }

        $isAjax        = $this->getRequest()->getParam('isAjax');
        $storeId       = (int)$this->getRequest()->getParam(PageInterface::STORE_ID);
        $savePageStore = (Store::DEFAULT_STORE_ID !== $storeId) && !$isAjax;

        $data = $this->getRequest()->getPostValue();

        if (isset($data[PageInterface::CATEGORIES])) {
            $data[PageInterface::CATEGORIES] = implode(',', $data[PageInterface::CATEGORIES]);
        } else {
            $data[PageInterface::CATEGORIES] = '';
        }

        if (isset($data[PageInterface::STORE_IDS])) {
            $data[PageInterface::STORE_IDS] = implode(',', $data[PageInterface::STORE_IDS]);
        }

        if (isset($data[PageInterface::PAGE_ID]) && !$data[PageInterface::PAGE_ID]) {
            unset($data[PageInterface::PAGE_ID]);
        }

        if (isset($data[PageInterface::IMAGE])) {
            $image = $data[PageInterface::IMAGE];

            $data[PageInterface::IMAGE] = $image[0]['name'] ?? '';
        } else {
            $data[PageInterface::IMAGE] = null;
        }

        try {
            if ($savePageStore) {
                $model->setStoreId($storeId)
                    ->setUseDefault($this->getRequest()->getParam(PageInterface::DEFAULT) ?? []);
            }

            $model->setData($data);

            if ($this->urlKeyExists($model, $storeId)) {
                throw new DuplicateException(
                    'Url key "' . $model->getUrlKey() . '" already exists for another page.'
                );
            }

            $this->pageRepository->save($model);

            $pageId = (int)$model->getId();

            $filters = $this->filterRepository->getByPageId((int)$model->getId());
            foreach ($filters as $filter) {
                $this->filterRepository->delete($filter);
            }

            if ($model->getId() && isset($data['filters'])) {
                $filters = [];
                foreach ($data['filters'] as $filter) {
                    if (!isset($filter['options']) || !isset($filter['attribute'])) {
                        continue;
                    }
                    if (isset($filters[$filter['attribute']])) {
                        $filters[$filter['attribute']] = array_unique(array_merge($filters[$filter['attribute']], $filter['options']));
                    } else {
                        $filters[$filter['attribute']] = $filter['options'];
                    }
                }
                foreach ($filters as $attribute => $options) {
                    $filterData                                  = [];
                    $filterModel                                 = $this->filterRepository->create();
                    $filterData[FilterInterface::OPTION_IDS]     = implode(',', $options);
                    $filterData[FilterInterface::ATTRIBUTE_ID]   = $attribute;
                    $filterData[FilterInterface::ATTRIBUTE_CODE] = $this->getAttributeCode($attribute);
                    $filterData[FilterInterface::PAGE_ID]        = $model->getId();
                    $filterModel->setData($filterData);
                    $this->filterRepository->save($filterModel);
                }
            }

            $this->messageManager->addSuccessMessage(__('You saved the page.'));

            if ($this->getRequest()->getParam('back') == 'edit') {
                return $resultRedirect->setPath('*/*/edit', $this->prepareRedirectParams($pageId, $storeId));
            }

        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }


        return $resultRedirect->setPath('*/*/');

    }

    public function getAttributeCode($attributeId): ?string
    {
        $attribute = $this->attribute->get($attributeId);

        return $attribute->getAttributeCode();
    }

    private function prepareRedirectParams(int $pageId, int $storeId): array
    {
        $params = [PageInterface::PAGE_ID => $pageId];
        if (Store::DEFAULT_STORE_ID !== $storeId) {
            $params[ScopeInterface::SCOPE_STORE] = $storeId;
        }

        return $params;
    }

    private function urlKeyExists(PageInterface $model, int $storeId): bool
    {
        $pageExist = $this->pageRepository->getCollection()
            ->addStoreFilter($storeId)
            ->addFieldToFilter(PageInterface::STORE_ID, $storeId)
            ->addFieldToFilter(PageInterface::URL_KEY, $model->getUrlKey());

        if ($model->getId()) {
            $pageExist->addFieldToFilter(PageInterface::PAGE_ID, ['neq' => $model->getId()]);
        }

        $pageExist = $pageExist->getSize();

        return boolval($pageExist);
    }

}
