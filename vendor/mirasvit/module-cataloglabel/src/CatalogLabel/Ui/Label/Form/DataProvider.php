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


namespace Mirasvit\CatalogLabel\Ui\Label\Form;

use Magento\Backend\Model\Url;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\FieldsetFactory;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Mirasvit\CatalogLabel\Model\Label\DisplayFactory as DisplayFactory;
use Mirasvit\CatalogLabel\Model\System\Config\Source\ImageType as ImageTypeSource;
use Mirasvit\CatalogLabel\Model\System\Config\Source\LabelAppearenceSource;
use Mirasvit\CatalogLabel\Repository\LabelRepository;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $uiComponentFactory;

    private $context;

    private $repository;

    private $imageTypeSource;

    private $displayFactory;

    private $mediaDir;

    private $fieldsetFactory;

    private $urlBuilder;

    private $configProvider;

    private $filesystem;

    private $appearenceSource;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Filesystem $filesystem,
        UiComponentFactory $uiComponentFactory,
        FieldsetFactory $fieldsetFactory,
        ContextInterface $context,
        LabelRepository $repository,
        ImageTypeSource $imageTypeSource,
        LabelAppearenceSource $labelAppearenceSource,
        DisplayFactory $displayFactory,
        ConfigProvider $configProvider,
        Url $urlBuilder,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->uiComponentFactory = $uiComponentFactory;
        $this->context            = $context;
        $this->repository         = $repository;
        $this->imageTypeSource    = $imageTypeSource;
        $this->displayFactory     = $displayFactory;
        $this->configProvider     = $configProvider;
        $this->filesystem         = $filesystem;
        $this->mediaDir           = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->fieldsetFactory    = $fieldsetFactory;
        $this->urlBuilder         = $urlBuilder;
        $this->appearenceSource   = $labelAppearenceSource;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $arrItems          = [];
        $arrItems['items'] = [];

        $previewTemplateUrl = $this->urlBuilder->getUrl('cataloglabel/label/previewTemplate');

        /** @var LabelInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $item->load($item->getId());

            $itemData = $item->getData();

            if ($itemData['active_from'] == '0000-00-00 00:00:00') {
                $itemData['active_from'] = null;
            }

            if ($itemData['active_to'] == '0000-00-00 00:00:00') {
                $itemData['active_to'] = null;
            }

            $itemData['type_disabled']        = true;
            $itemData['attribute_disabled']   = isset($itemData['attribute_id']) && $itemData['attribute_id'];
            $itemData['template_preview_url'] = $previewTemplateUrl;

            $tabsData['general'] = $itemData;

            foreach ($this->appearenceSource->toArray() as $type => $label) {
                $displays = $item->getDisplays()->addFieldToFilter(DisplayInterface::TYPE, $type);

                if ($item->getType() == LabelInterface::TYPE_RULE) {
                    $tabsData['display'][$type]['display_data'] = $this->prepareDisplay($type, $displays->getFirstItem());
                } else {
                    /** @var DisplayInterface $display */
                    foreach ($displays as $display) {
                        $tabsData['display'][$display->getAttributeOptionId()][$type]['display_data'] = $this->prepareDisplay($type, $display);
                    }
                }
            }

            $arrItems[$item->getId()] = $tabsData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    private function prepareDisplay(string $type, ?DisplayInterface $display = null): array
    {
        if (!$display) {
            $display = $this->displayFactory->create()->setType($type);
        }

        $data = $display->getData();

        if (!isset($data[DisplayInterface::TEMPLATE_ID]) || is_null($data[DisplayInterface::TEMPLATE_ID])) {
            $data[DisplayInterface::TEMPLATE_ID] = '0';
        }

        if (($imagePath = $display->getImagePath()) && $this->mediaDir->isExist('cataloglabel/'.$imagePath)) {
            $data['image'][] = [
                'image_path' => $imagePath,
                'url'        => $this->configProvider->getBaseMediaUrl() . '/' . $imagePath,
                'name'       => $imagePath,
                'type'       => mime_content_type($this->configProvider->getBaseMediaPath() . '/' . $imagePath),
                'size'       => $this->mediaDir->stat($this->configProvider->getBaseMediaPath() . '/' . $imagePath)['size']
            ];
        }

        return $data;
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();

        $model = $this->getModel();
        if (!$model) {
            return $meta;
        }

        if (!$model->getType()) {
            return $meta;
        }

        $meta = $this->prepareForm($model);

        return $meta;
    }

    protected function prepareForm(LabelInterface $model): array
    {
        $uiComponent = 'cataloglabel_label_form_' . $model->getType();

        $component = $this->uiComponentFactory->create($uiComponent);

        if ($model->getType() == 'rule') {
            $component = $this->addDisplayConfigs(
                $model,
                $component,
                'display',
                (string)__('Design')
            );
        } else {
            /** @var Fieldset $attributeFieldset */
            $attributeFieldset = $this->fieldsetFactory->create();
            $attributeFieldset->setData([
                'name'   => 'display',
                'config' => [
                    'componentType'     => 'fieldset',
                    'label'             => 'Gallery',
                    'additionalClasses' => 'gallery_wrapper'
                ]
            ]);

            $attribute = $model->getAttribute();

            foreach ($attribute->getOptions() as $attributeOption) {
                if (!$attributeOption->getValue()) {
                    continue;
                }

                $attributeFieldset = $this->addDisplayConfigs(
                    $model,
                    $attributeFieldset,
                    (string)$attributeOption->getValue(),
                    (string)$attributeOption->getLabel()
                );
            }

            $component->addComponent('display', $attributeFieldset);
        }

        $data = $this->prepareComponent($component);

        return $data['children'];
    }

    private function addDisplayConfigs(
        LabelInterface $model,
        UiComponentInterface $component,
        string $formName,
        string $label
    ): UiComponentInterface {
        $appearence = explode(',', $model->getAppearence());

        $displayFieldset = $this->fieldsetFactory->create();
        $displayFieldset->setData([
            'name'   => $formName,
            'config' => [
                'componentType' => 'fieldset',
                'collapsible'   => $formName == 'display' ? false : true,
                'label'         => $label
            ]
        ]);

        foreach ($appearence as $mode) {
            $optionChildren = $this->uiComponentFactory->create('cataloglabel_label_form_display');
            $optionChildren->setData([
                'name'   => $mode,
                'config' => [
                    'componentType'     => 'fieldset',
                    'collapsible'       => false,
                    'label'             => $this->appearenceSource->toArray()[$mode],
                    'additionalClasses' => $formName == 'display' ? $mode : $mode . ' ' . 'value-id-' . $formName
                ]
            ]);

            $displayFieldset->addComponent($mode, $optionChildren);
        }

        $component->addComponent($formName, $displayFieldset);

        return $component;
    }

    protected function prepareComponent(UiComponentInterface $component): array
    {
        $data = [];
        foreach ($component->getChildComponents() as $name => $child) {
            $data['children'][$name] = $this->prepareComponent($child);
        }

        $data['arguments']['data']  = $component->getData();
        $data['arguments']['block'] = $component->getBlock();

        return $data;
    }

    private function getModel()
    {
        $id = $this->context->getRequestParam($this->getRequestFieldName(), null);

        return $id ? $this->repository->get((int)$id) : false;
    }
}
