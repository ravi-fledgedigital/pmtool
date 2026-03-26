<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Adminhtml\Ui\Component\Form\Element\MultiSelect;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Ui\Component\Form\Element\MultiSelect;

class UpdateStoreDefaultValue
{
    public function __construct(
        private readonly Data $helper,
        private readonly RequestInterface $request,
        private readonly PageRepository $pageRepository,
        private readonly BlockRepository $blockRepository
    ) {
    }

    public function afterPrepare(MultiSelect $subject): void
    {
        if ($subject->getName() === 'storeviews'
            && $rule = $this->helper->currentRule()
        ) {
            $allowedStores = $rule->getScopeStoreviews();

            if ($allowedStores) {
                $config = $subject->getData('config') ?? [];
                if (isset($config['options']) && isset($config['default'])) {
                    $config['default'] = (string)current($allowedStores);
                }

                if ($this->isCmsEntity()) {
                    $config = $this->prepareCmsStoreValueField($config);
                }

                $subject->setData('config', $config);
            }
        }
    }

    private function isCmsEntity(): bool
    {
        return ($this->request->getModuleName() === 'cms'
            && in_array($this->request->getControllerName(), ['page', 'block']))
            || in_array($this->request->getControllerName(), ['cms_page', 'cms_block']);
    }

    private function prepareCmsStoreValueField(array $config): array
    {
        try {
            $cmsEntity = false;
            if ($entityId = $this->request->getParam('page_id')) {
                $cmsEntity = $this->pageRepository->getById($entityId);
            } elseif ($entityId = $this->request->getParam('block_id')) {
                $cmsEntity = $this->blockRepository->getById($entityId);
            }

            if ($cmsEntity) {
                $origStores = $cmsEntity->getOrigData('store_id');
                $origStores = is_array($origStores)
                    ? $origStores
                    : array_filter(explode(',', (string)$origStores));

                if (!in_array(Store::DEFAULT_STORE_ID, $origStores)) {
                    $rule = $this->helper->currentRule();
                    if (array_diff($origStores, $rule->getScopeStoreviews())) {
                        $config['validation']['required-entry'] = false;
                    } else {
                        $config['notice'] = __('The page must be assigned to at least one store view.'
                            . ' If you want to hide the page, disable it instead.');
                    }
                }
            }
            //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (NoSuchEntityException $e) {
        }

        return $config;
    }
}
