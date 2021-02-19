<?php

namespace A3020\ImageOptimizer;

use InvalidArgumentException;

class Image
{
    protected $pathToImage = '';

    /**
     * @param string $pathToImage
     */
    public function __construct($pathToImage)
    {
        if (! file_exists($pathToImage)) {
            throw new InvalidArgumentException("`{$pathToImage}` does not exist");
        }

        $this->pathToImage = $pathToImage;
    }

    /**
     * @return string
     */
    public function mime()
    {
        return mime_content_type($this->pathToImage);
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->pathToImage;
    }

    /**
     * @return string
     */
    public function extension()
    {
        $extension = pathinfo($this->pathToImage, PATHINFO_EXTENSION);

        return strtolower($extension);
    }
}
