<?php 
namespace Cpss\Crm\Helper;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Glob;

class File
{
    private $filesystem;
    private $fileDriver;

    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        FileDriver $fileDriver,
        Glob $glob
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
        $this->glob = $glob;
    }
     
    /**
     * getVarDirectory
     *
     * @return WriteInterface
     */
    public function getVarDirectory(){
        return $this->filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
    }

    /**
     * saveToFile
     *
     * @param  string $filePath
     * @param  string $data
     * @return string
     */
    public function saveToFile($filePath, $content)
    {
        return $this->write($this->getVarDirectory(), $filePath, $content);
    }
 
    /**
     * Write content to text file
     *
     * @param WriteInterface $writeDirectory
     * @param $filePath
     * @return bool
     * @throws FileSystemException
     */
    public function write(WriteInterface $writeDirectory, string $filePath, string $content)
    {
        $stream = $writeDirectory->openFile($filePath, 'w+');
        $stream->lock();
        $fileData = $content;
        $stream->write($fileData);
        $stream->unlock();
        $stream->close();
        return true;
    }

    /**
     * readFile
     *
     * @param  string $path
     * @return string
     */
    public function readFile($path)
    {
        return $this->fileDriver->fileGetContents($path);
    }

    public function getVarPath(){
        return $this->directoryList->getPath('var').DIRECTORY_SEPARATOR;
    }


    /**
     * scanFiles
     * @param string $dir
     * 
     * @return array
     */
    public function scanFiles($folder)
    {
        $dir = $this->getFilePath($folder);
        return $this->fileDriver->readDirectory($dir);
    }
    


    /**
     * getFilePath
     *
     * @return string
     */
    public function getFilePath($folder)
    {
        $varPath = $this->directoryList->getPath('var');
        return $varPath . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
    }
}