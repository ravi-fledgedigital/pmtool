<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File\Uploader;

use OnitsukaTiger\OrderAttribute\Api\CheckoutAttributeRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class Validator
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CheckoutAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param CheckoutAttributeRepositoryInterface $attributeRepository
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        CheckoutAttributeRepositoryInterface $attributeRepository,
        ObjectManagerInterface $objectManager
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * Validate file attribute
     *
     * @param string $attributeCode
     * @return bool
     */
    public function validateAttributeCode(string $attributeCode): bool
    {
        return (bool)$this->getFileAttribute($attributeCode);
    }

    /**
     * Validate file by validation rules
     *
     * @param string $attributeCode
     * @param string[] $fileInfo
     * @return bool
     */
    public function validateFile(string $attributeCode, array $fileInfo): bool
    {
        if ($attribute = $this->getFileAttribute($attributeCode)) {
            /** @var File $dataModel */
            $dataModel = $this->objectManager->create($attribute->getDataModel());
            $result = $dataModel
                ->setAttribute($attribute)
                ->validateTmpValue($fileInfo);

            return empty($result);
        }

        return false;
    }

    /**
     * Retrieve file attribute
     *
     * @param string $code
     * @return CheckoutAttributeInterface|false
     */
    private function getFileAttribute(string $code)
    {
        try {
            $attribute = $this->attributeRepository->get($code);
            $attribute = $attribute->getFrontendInput() == 'file' ? $attribute : false;
        } catch (LocalizedException $e) {
            $attribute = false;
        }

        return $attribute;
    }
}
