<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsv\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteFactory;
use Magento\ImportCsvApi\Api\Data\LocalizedSourceDataInterface;
use Magento\ImportCsvApi\Api\Data\SourceDataInterface;
use Magento\ImportCsvApi\Api\StartImportInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\ImportExport\Model\ImportFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StartImport implements StartImportInterface
{
    /**
     * @param CsvFactory $csvFactory
     * @param Filesystem $filesystem
     * @param WriteFactory $writeFactory
     * @param LoggerInterface $logger
     * @param ImportFactory $importFactory
     */
    public function __construct(
        private readonly CsvFactory $csvFactory,
        private readonly Filesystem $filesystem,
        private readonly WriteFactory $writeFactory,
        private readonly LoggerInterface $logger,
        private readonly ImportFactory $importFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(
        SourceDataInterface $source
    ): array {
        $sourceAsArray = $this->getDataAsArray($source);
        /** @var Import $import */
        $import = $this->importFactory->create();
        $import->setData($sourceAsArray);
        unset($sourceAsArray);
        $errors = [];
        try {
            $importAdapter = $this->createImportAdapter($source->getCsvData(), $source->getImportFieldSeparator());
            $errors = $this->validate($import, $importAdapter);
        } catch (LocalizedException $e) {
            $errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $errors[] = __('Sorry, but the data is invalid or the file is not uploaded.');
        }
        if (!$errors) {
            $processedEntities = $import->getProcessedEntitiesCount();
            $errorAggregator = $import->getErrorAggregator();
            $errorAggregator->initValidationStrategy(
                $import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY),
                $import->getData(Import::FIELD_NAME_ALLOWED_ERROR_COUNT)
            );
            $errorAggregator->clear();
            try {
                $import->importSource();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
            if ($import->getErrorAggregator()->hasToBeTerminated()) {
                $errors[] = __('Maximum error count has been reached or system error is occurred!');
            } else {
                $import->invalidateIndex();
            }
            if (!$errors) {
                $errors = [__('Entities Processed: %1', $processedEntities)];
            }
        }

        return array_map(fn (mixed $error) => (string) $error, $errors);
    }

    /**
     * Converts the source data to an array for Import
     *
     * @param SourceDataInterface $sourceData
     * @return array
     */
    private function getDataAsArray(SourceDataInterface $sourceData): array
    {
        $array = [
            'entity' => $sourceData->getEntity(),
            'behavior' => $sourceData->getBehavior(),
            Import::FIELD_NAME_VALIDATION_STRATEGY => $sourceData->getValidationStrategy(),
            Import::FIELD_NAME_ALLOWED_ERROR_COUNT => $sourceData->getAllowedErrorCount(),
            Import::FIELD_FIELD_SEPARATOR => $sourceData->getImportFieldSeparator(),
            'locale' => $sourceData instanceof LocalizedSourceDataInterface ? $sourceData->getLocale() : null
        ];
        if (null !== $sourceData->getImportFieldSeparator()) {
            $array[Import::FIELD_FIELD_SEPARATOR] = $sourceData->getImportFieldSeparator();
        }
        if (null !== $sourceData->getImportMultipleValueSeparator()) {
            $array[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR] = $sourceData->getImportMultipleValueSeparator();
        }
        if (null !== $sourceData->getImportEmptyAttributeValueConstant()) {
            $array[Import::FIELD_EMPTY_ATTRIBUTE_VALUE_CONSTANT] = $sourceData->getImportEmptyAttributeValueConstant();
        }
        if (null !== $sourceData->getImportImagesFileDir()) {
            $array[Import::FIELD_NAME_IMG_FILE_DIR] = $sourceData->getImportImagesFileDir();
        }
        return $array;
    }

    /**
     * Base64 decodes data,  decompresses if gz compressed, stores in memory or temp file, and loads CSV adapter
     *
     * @param string $importData
     * @param ?string $delimiter
     * @return AbstractSource
     */
    private function createImportAdapter(string $importData, ?string $delimiter): AbstractSource
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $importData = base64_decode($importData);
        if (0 === strncmp("\x1f\x8b", $importData, 2)) { // gz's magic string
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $importData = gzdecode($importData);
        }
        $openedFile = $this->writeFactory->create('php://temp', '', 'w');
        $openedFile->write($importData);
        unset($importData);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $parameters = ['directory' => $directory, 'file' => $openedFile];
        if (!empty($delimiter)) {
            $parameters['delimiter'] = $delimiter;
        }
        $adapter = $this->csvFactory->create($parameters);
        return $adapter;
    }

    /**
     * Process validation result and add required error or success messages to Result block
     *
     * @param Import $import
     * @param AbstractSource $source
     * @return array
     * @throws LocalizedException
     */
    private function validate(Import $import, AbstractSource $source): array
    {
        $errors = [];
        $validationResult = $import->validateSource($source);
        $errorAggregator = $import->getErrorAggregator();

        if ($import->getProcessedRowsCount() || $errorAggregator->getErrorsCount()) {
            if ($validationResult && $import->getProcessedRowsCount() && !$import->isImportAllowed()) {
                $errors[] = __('The file is valid, but we can\'t import it for some reason.');
            } elseif ($errorAggregator->getErrorsCount() && !$this->canSkipValidationErrors($import)) {
                foreach ($errorAggregator->getAllErrors() as $error) {
                    $errors[] = __('Row %1: %2', $error->getRowNumber() + 1, $error->getErrorMessage());
                }
                return $errors;
            }
        } else {
            $errors[] = __('This file is empty. Please try another one.');
        }
        return $errors;
    }

    /**
     * Check whether validation errors can be skipped
     *
     * @param Import $import
     * @return bool
     */
    private function canSkipValidationErrors(Import $import): bool
    {
        $validationStrategy = $import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY);
        return $validationStrategy === ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS
            // At least one row is valid
            && $import->getValidatedIds();
    }
}
