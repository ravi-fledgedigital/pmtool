<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

/**
 * Class Zip
 *
 * Creating zip archive content (string) from files' contents
 */
class Zip
{
    /**
     * @var array
     */
    protected $files = [];

    /**
     * Adding file to zip archive
     *
     * @param string $fileName
     * @param string $fileContent
     */
    public function addFileFromString($fileName, $fileContent)
    {
        $this->files[$fileName] = $fileContent;
    }

    /**
     * @return int
     */
    public function countFiles()
    {
        return count($this->files);
    }

    /**
     * Render zip archive content
     * @return string
     */
    public function render()
    {
        $output = "";
        $written = 0;
        $dictionary = [];
        foreach ($this->files as $filename => $content) {
            $fileInfo = [
                'versionToExtract'      => 10,
                'generalPurposeBitFlag' => 0,
                'compressionMethod'     => 0,
                'modificationTime'      => 28021,
                'modificationDate'      => 20072,
                'crc32'                 => hexdec(hash('crc32b', $content)),
                'compressedSize'        => $size = strlen($content),
                'uncompressedSize'      => $size,
                'filenameLength'        => strlen($filename),
                'extraFieldLength'      => 0,
            ];

            $LFH = pack(
                'LSSSSSLLLSSa*',
                ...array_values(
                    [
                        'signature' => 0x04034b50,
                    ] +
                    $fileInfo +
                    ['filename' => $filename]
                )
            );

            $dictionary[$filename] = [
                    'signature'     => 0x02014b50,
                    'versionMadeBy' => 798,
                ] + $fileInfo + [
                    'fileCommentLength'      => 0,
                    'diskNumber'             => 0,
                    'internalFileAttributes' => 0,
                    'externalFileAttributes' => 2176057344,
                    'localFileHeaderOffset'  => $written,
                    'filename'               => $filename,
                ];

            $output .= $LFH;
            $output .= $content;
            $written = strlen($output);
        }

        $EOCD = [
            'signature'                    => 0x06054b50,
            'diskNumber'                   => 0,
            'startDiskNumber'              => 0,
            'numberCentralDirectoryRecord' => $records = count($dictionary),
            'totalCentralDirectoryRecord'  => $records,
            'sizeOfCentralDirectory'       => 0,
            'centralDirectoryOffset'       => $written,
            'commentLength'                => 0
        ];

        foreach ($dictionary as $entryInfo) {
            $CDFH = pack('LSSSSSSLLLSSSSSLLa*', ...array_values($entryInfo));
            $output .= $CDFH;
        }
        $written = strlen($output);

        $EOCD['sizeOfCentralDirectory'] = $written - $EOCD['centralDirectoryOffset'];
        $EOCD = pack('LSSSSLLS', ...array_values($EOCD));
        $output .= $EOCD;

        return $output;
    }
}
