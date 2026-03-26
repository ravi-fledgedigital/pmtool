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

namespace Mirasvit\LandingPage\Ui\Page\Form;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\Store;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\LandingPage\Api\Data\FilterInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;
use Mirasvit\LandingPage\Service\ImageUrlService;

class DataProvider extends AbstractDataProvider
{
    protected $collection;

    private $pageRepository;

    private $filterRepository;

    private $imageUploader;

    private $imageUrlService;

    private $mime;

    private $mediaDirectory;

    private $context;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        FilterRepository $filterRepository,
        PageRepository   $pageRepository,
        ImageUploader    $imageUploader,
        ImageUrlService  $imageUrlService,
        Filesystem       $filesystem,
        Mime             $mime,
        string           $name,
        string           $primaryFieldName,
        string           $requestFieldName,
        ContextInterface $context,
        array            $meta = [],
        array            $data = []
    ) {
        $this->filterRepository = $filterRepository;
        $this->collection       = $pageRepository->getCollection();
        $this->pageRepository   = $pageRepository;
        $this->mediaDirectory   = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->imageUploader    = $imageUploader;
        $this->imageUrlService  = $imageUrlService;
        $this->mime             = $mime;
        $this->context          = $context;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $result     = [];
        $filterData = [];
        $storeId    = (int)$this->context->getRequestParam('store');

        if ($model = $this->getModel($storeId)) {
            $pageData         = $model->getData();
            $filterCollection = $this->filterRepository->getByPageId((int)$model->getId());

            foreach ($filterCollection->getItems() as $filter) {
                $data              = $filter->getData();
                $data['attribute'] = $data[FilterInterface::ATTRIBUTE_ID];
                $data['options']   = explode(',', $data[FilterInterface::OPTION_IDS]);
                $filterData[]      = $data;
            }

            if (isset($pageData[PageInterface::CATEGORIES])) {
                $pageData[PageInterface::CATEGORIES] = explode(',', $pageData[PageInterface::CATEGORIES]);
            }

            $pageData[PageInterface::DISABLE_DEFAULT] = $storeId !== Store::DEFAULT_STORE_ID;
            $pageData[PageInterface::STORE_IDS]       = explode(',', $pageData[PageInterface::STORE_IDS]);
            $pageData[PageInterface::FILTERS]         = $filterData;
            $pageData[PageInterface::STORE_ID]        = $storeId;

            unset($pageData[PageInterface::DEFAULT]);
            $useDefault = $model->getUseDefault();

            if (Store::DEFAULT_STORE_ID !== $storeId) {
                foreach (PageInterface::STORE_FIELDS as $field) {
                    $pageData[PageInterface::DEFAULT . '[' . $field . ']'] = isset($useDefault[$field]) && (int)$useDefault[$field];
                }
            }

            $pageData[PageInterface::IMAGE] = $this->prepareImageData($pageData);

            $result[$model->getId()] = $pageData;
        }

        return $result;
    }

    private function getModel(int $storeId): ?PageInterface
    {
        $id = $this->context->getRequestParam(PageInterface::PAGE_ID, null);

        return $id ? $this->pageRepository->get((int)$id, (int)$storeId) : null;
    }

    private function prepareImageData(array $data): ?array
    {
        if (isset($data[PageInterface::IMAGE])) {
            $imageName = $data[PageInterface::IMAGE];
            if ($this->mediaDirectory->isExist($this->getFilePath($imageName))) {
                return [
                    [
                        'name' => $imageName,
                        'url'  => $this->imageUrlService->getImageUrl($imageName),
                        'size' => $this->mediaDirectory->stat($this->getFilePath($imageName))['size'],
                        'type' => $this->getMimeType($imageName),
                    ],
                ];
            }
        }

        return null;
    }

    private function getMimeType(string $fileName)
    {
        $absoluteFilePath = $this->mediaDirectory->getAbsolutePath($this->getFilePath($fileName));

        return $this->mime->getMimeType($absoluteFilePath);
    }

    private function getFilePath(string $fileName)
    {
        return $this->imageUploader->getFilePath($this->imageUploader->getBasePath(), $fileName);
    }
}
