<?php
namespace OnitsukaTiger\Cms\Helper;

class Image
{
    const VECTOR_EXTENSIONS = 'svg';

    /**
     * Check if the file is a vector image
     *
     * @param $file
     * @return bool
     */
    public function isVectorImage($file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, $this->getVectorExtensions());
    }

    /**
     * get vector images extensions
     *
     * @return array
     */
    public function getVectorExtensions()
    {
        return [self::VECTOR_EXTENSIONS];
    }
}
