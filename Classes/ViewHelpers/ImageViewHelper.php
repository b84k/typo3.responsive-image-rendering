<?php declare(strict_types=1);
/**
 * This file is part of ViktorFirus\ResponsiveImageRendering.
 *
 * ViktorFirus\ResponsiveImageRendering is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * ViktorFirus\ResponsiveImageRendering is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ViktorFirus\ResponsiveImageRendering or see <http://www.gnu.org/licenses/>.
 */

namespace ViktorFirus\ResponsiveImageRendering\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use ViktorFirus\ResponsiveImageRendering\Utility\ResponsiveImage;

/**
 * @author Viktor Firus <viktor@firus.eu>
 */
class ImageViewHelper extends AbstractViewHelper
{
    /**
     * @var ImageService
     */
    protected $imageService;

    /**
     * @param ImageService $imageService
     */
    public function injectImageService(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @param FileReference $fileReference
     * @param array $responsiveSizes
     * @param int $defaultMaxWidth
     * @param string $aspectRatio
     * @param int $width
     * @param bool $plainCssClass
     * @param string $alt
     * @param string $title
     *
     * @return string
     */
    public function render(
        FileReference $fileReference,
        array $responsiveSizes,
        int $defaultMaxWidth,
        string $aspectRatio = '0x0',
        int $width = 0,
        bool $plainCssClass = false,
        string $alt = '',
        string $title = ''
    ): string
    {
        list($aspectRatioWidth, $aspectRatioHeight) = $this->parseAspectRatio($aspectRatio);

        /** @var ResponsiveImage $responsiveImage */
        $responsiveImage = $this->objectManager->get(
            ResponsiveImage::class,
            $fileReference,
            $responsiveSizes,
            $defaultMaxWidth
        );
        if ($aspectRatioWidth > 0 && $aspectRatioHeight > 0) {
            $responsiveImage->setAspectRatio($aspectRatioWidth, $aspectRatioHeight);
        }
        $responsiveImage->render();
        if ($plainCssClass) {
            return $responsiveImage->getCssClassName();
        }
        $responsiveImage->setAlternative($alt);
        $responsiveImage->setTitle($title);
        $responsiveImage->setWidth($width);

        return $responsiveImage->getImgTag();
    }

    /**
     * @param string $aspectRatio
     *
     * @return array
     */
    protected function parseAspectRatio(string $aspectRatio)
    {
        $width = (float)explode('x', $aspectRatio)[0];
        $height = (float)explode('x', $aspectRatio)[1];

        if ($width != 0 && $height == 0 || $width == 0 && $height != 0) {
            throw new \InvalidArgumentException('You must either specify "width" and "height" of aspect ratio ' .
                'or ignore both.');
        }

        return [$width, $height];
    }
}
