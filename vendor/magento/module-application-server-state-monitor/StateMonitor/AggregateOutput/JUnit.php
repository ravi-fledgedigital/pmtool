<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor\AggregateOutput;

use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList2;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileSystemFile;

/**
 * JUnit output for AggregateOutput of StateMonitor
 */
class JUnit implements OutputInterface
{
    /**
     * @param DirectoryList $directoryList
     * @param FileSystemFile $fileSystemFile
     */
    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly FileSystemFile $fileSystemFile,
    ) {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPCS.Magento2.Files.LineLength.MaxExceeded)
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function doOutput(array $errorsByClassName, array $errorsByRequestName) : string
    {
        $timestamp = gmdate('Y-m-d\\TH:i:sp');
        $tempDirectory = $this->directoryList->getPath(DirectoryList2::TMP);
        // Note: Magento doesn't currently have tempnam in its abstractions.
        $tempFileNameToCleanup = tempnam($tempDirectory, 'StateMonitor-junit-' . $timestamp . '-');
        $tempFileName = $tempFileNameToCleanup . '.xml';
        $errorsByClassNameCount = count($errorsByClassName);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $output = <<<EOD
        <?xml version="1.0" encoding="UTF-8"?>
        <testsuites>
            <testsuite name="errorsByClassNameCount" tests="{$errorsByClassNameCount}" assertions="{$errorsByClassNameCount}" errors="0" warnings="0" failures="{$errorsByClassNameCount}" skipped="0" time="0.0">

        EOD;
        // phpcs:enable Generic.Files.LineLength.TooLong
        foreach ($errorsByClassName as $className => $error) {
            $escapedClassName = htmlspecialchars($className, ENT_QUOTES | ENT_XML1);
            $escapedError = htmlspecialchars(var_export($error, true), ENT_XML1);
            $output .= <<<EOD
                    <testcase name="{$escapedClassName}" time="0.0">
                        <failure type="stateChange">{$escapedError}</failure>
                    </testcase >

            EOD;
        }
        $output .= <<<EOD
            </testsuite>

        EOD;
        $errorsByRequestNameCount = 0;
        $innerOutput = '';
        foreach ($errorsByRequestName as $requestName => $currentErrorsByClassName) {
            $escapedRequestName = htmlspecialchars($requestName, ENT_QUOTES | ENT_XML1);
            $currentErrorsByClassNameCount = count($currentErrorsByClassName);
            $errorsByRequestNameCount += $currentErrorsByClassNameCount;
            // phpcs:disable Generic.Files.LineLength.TooLong
            $innerOutput .= <<<EOD
                    <testsuite name="$escapedRequestName" tests="{$currentErrorsByClassNameCount}" assertions="{$currentErrorsByClassNameCount}" errors="0" warnings="0" failures="{$currentErrorsByClassNameCount}" skipped="0" time="0.0">

            EOD;
            // phpcs:enable Generic.Files.LineLength.TooLong
            foreach ($currentErrorsByClassName as $className => $error) {
                $escapedClassName = htmlspecialchars($className, ENT_QUOTES | ENT_XML1);
                $escapedError = htmlspecialchars(var_export($error, true), ENT_XML1);
                $innerOutput .= <<<EOD
                            <testcase name="{$escapedClassName}" time="0.0">
                                <failure type="stateChange">{$escapedError}</failure>
                            </testcase >

                EOD;
            }
            $innerOutput .= <<<EOD
                    </testsuite>

            EOD;
        }
        // phpcs:disable Generic.Files.LineLength.TooLong
        $output .= <<<EOD
            <testsuite name="errorsByRequestNameCount" tests="{$errorsByRequestNameCount}" assertions="{$errorsByRequestNameCount}" errors="0" warnings="0" failures="{$errorsByRequestNameCount}" skipped="0" time="0.0">

        EOD;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $output .= $innerOutput;
        $output .= <<<EOD
            </testsuite>
        </testsuites>

        EOD;
        $this->fileSystemFile->filePutContents($tempFileName, $output, 0);
        $this->fileSystemFile->deleteFile($tempFileNameToCleanup);
        return $tempFileName;
    }
}
