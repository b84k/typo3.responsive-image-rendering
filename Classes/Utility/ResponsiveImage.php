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

namespace ViktorFirus\ResponsiveImageRendering\Utility;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * @author Viktor Firus <viktor@firus.eu>
 */
class ResponsiveImage
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var FileReference
     */
    protected $fileReference;

    /**
     * @var array
     */
    protected $responsiveSizes;

    /**
     * @var string
     */
    protected $defaultImage;

    /**
     * @var array
     */
    protected $responsiveImages;

    /**
     * @var string
     */
    protected $cssClassName;

    /**
     * @var int
     */
    protected $defaultMaxWidth = 0;

    /**
     * @var float
     */
    protected $aspectRatioWidth = 0;

    /**
     * @var float
     */
    protected $aspectRatioHeight = 0;

    /**
     * @var string
     */
    protected $alternative = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $width = '0';

    /**
     * @var array
     */
    protected $cssClassesAddedToPageRenderer = [];

    /**
     * @var array
     */
    protected $processedImages = [];

    /**
     * @var int
     */
    protected $resourceWidth;

    /**
     * @var int
     */
    protected $resourceHeight;

    /**
     * @var string
     */
    protected $class = '';

    /**
     * @var string
     */
    protected $style = '';

    /**
     * @var bool
     */
    protected $absolute = false;

    /**
     * @param FileReference $fileReference
     * @param array $responsiveSizes
     * @param int $defaultMaxWidth
     */
    public function __construct(FileReference $fileReference, array $responsiveSizes = [], int $defaultMaxWidth = 0)
    {
        $this->setFile($fileReference);
        $this->setResponsiveSizes($responsiveSizes);
        $this->setDefaultMaxWidth($defaultMaxWidth);

        $filePath = $this->fileReference->getOriginalFile()->getPublicUrl();
        $url = parse_url($filePath);

        // Set the default width and height for the resource
        $this->resourceWidth = 1;
        $this->resourceHeight = 1;

        // Get resource width and height only, if the file path is not an URL
        if (!isset($url['scheme']) && !isset($url['host'])) {
            $filePath = PATH_site . $filePath;
            if (file_exists($filePath)) {
                $imageSize = getimagesize($filePath);
                $this->resourceWidth = $imageSize[0];
                $this->resourceHeight = $imageSize[1];
            }
        }
    }

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * @param FileReference $fileReference
     */
    public function setFile(FileReference $fileReference)
    {
        $this->fileReference = $fileReference;
    }

    /**
     * @param array $responsiveSizes
     */
    public function setResponsiveSizes(array $responsiveSizes)
    {
        $this->validateResponsiveSizes($responsiveSizes);
        $this->responsiveSizes = $responsiveSizes;
    }

    /**
     * @param int $defaultMaxWidth
     */
    public function setDefaultMaxWidth(int $defaultMaxWidth)
    {
        $this->defaultMaxWidth = $defaultMaxWidth;
    }

    /**
     * @param float $width
     * @param float $height
     */
    public function setAspectRatio(float $width, float $height)
    {
        $this->aspectRatioWidth = $width;
        $this->aspectRatioHeight = $height;
    }

    /**
     * @param string $alternative
     */
    public function setAlternative(string $alternative)
    {
        $this->alternative = $alternative;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $width
     */
    public function setWidth(string $width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle(string $style)
    {
        $this->style = $style;
    }

    /**
     * @return bool
     */
    public function getAbsolute(): bool
    {
        return $this->absolute;
    }

    /**
     * @param bool $absolute
     */
    public function setAbsolute(bool $absolute)
    {
        $this->absolute = $absolute;
    }

    /**
     * @return array
     */
    public function getProcessedImages(): array
    {
        return $this->processedImages;
    }

    public function render()
    {
        $crop = $this->generateCrop();

        $this->defaultImage = $this->processImage($this->getProcessInstruction($crop, $this->defaultMaxWidth));
        $this->processedImages['default'] = $this->defaultImage;

        $this->responsiveImages = [];
        foreach ($this->responsiveSizes as $viewPort => $size) {
            $this->responsiveImages[$viewPort] = $this->processImage($this->getProcessInstruction($crop, (int)$size));
            $this->processedImages['responsive'][$size] = $this->responsiveImages[$viewPort];
        }
    }

    /**
     * @return string
     */
    public function getCssClassName(): string
    {
        if (empty($this->defaultImage) || empty($this->responsiveImages)) {
            return '';
        }
        $this->cssClassName = $this->generateCssClassName();
        $this->addCssStyleDefinitionToPageHeader();

        return $this->cssClassName;
    }

    /**
     * @return string
     */
    public function getImgTag(): string
    {
        // Only return an empty string if both the default image and the responsive images are empty
        if (empty($this->defaultImage) && empty($this->responsiveImages)) {
            return '';
        }

        /** @var TagBuilder $tagBuilder */
        $tagBuilder = $this->objectManager->get(TagBuilder::class);
        $tagBuilder->setTagName('img');

        // Add src attribute
        $src = $this->defaultImage;
        if ($this->absolute) {
            $src = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/' . $src;
        }
        $tagBuilder->addAttribute('src', $src);

        // Add srcset if responsive images are available
        if (!empty($this->responsiveImages)) {
            $srcset = [];
            foreach ($this->responsiveImages as $viewPort => $responsiveImage) {
                $srcset[] = $responsiveImage . ' ' . $viewPort;
            }
            $tagBuilder->addAttribute('srcset', implode(', ', $srcset));
        }

        // Add alt attribute
        $alt = $this->fileReference->getProperty('alternative') ?: $this->alternative;
        $tagBuilder->addAttribute('alt', $alt);

        // Add title attribute
        $title = $this->fileReference->getProperty('title') ?: $this->title;
        $tagBuilder->addAttribute('title', $title);

        // Add width attribute
        if ($this->width > 0) {
            $tagBuilder->addAttribute('width', $this->width);
        }

        // Add class attribute
        if (!empty($this->class)) {
            $tagBuilder->addAttribute('class', $this->class);
        }

        // Add style attribute
        if (!empty($this->style)) {
            $tagBuilder->addAttribute('style', $this->style);
        }

        return $tagBuilder->render();
    }

    /**
     * @return string
     */
    protected function generateCssClassName(): string
    {
        $publicUrl = preg_replace('/[\/\.]/', '-', $this->fileReference->getPublicUrl());
        $publicUrl = preg_replace('/[^a-zA-Z0-9-]/', '', $publicUrl);
        $stringToHash = '';
        foreach ($this->responsiveSizes as $viewport => $width) {
            $stringToHash .= $viewport . '-' . $width . '_';
        }
        $stringToHash .= $this->defaultMaxWidth . '_' . $this->aspectRatioWidth . 'x' . $this->aspectRatioHeight;

        return $publicUrl . '-' . md5($stringToHash);
    }

    protected function addCssStyleDefinitionToPageHeader()
    {
        if ($this->cssClassesAddedToPageRenderer[$this->cssClassName] === true) {
            return;
        }
        $this->cssClassesAddedToPageRenderer[$this->cssClassName] = true;
        $cssClasses = '.' . $this->cssClassName . '{' .
            'background-image:url(' . $this->defaultImage . ');' .
            '}';
        $cssMediaQueries = '';
        foreach ($this->responsiveImages as $viewport => $image) {
            $cssMediaQueries = $cssMediaQueries . '@media(min-width:' . (int)$viewport . 'px){.' .
                $this->cssClassName . '{background-image:url("' . $image . '");}}';
        }
        $this->pageRenderer->addCssInlineBlock($this->cssClassName, $cssClasses . $cssMediaQueries, true);
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $crop
     *
     * @return array
     */
    protected function getSize(int $width, int $height, string $crop): array
    {
        if (empty($crop)) {
            return [$width, $height];
        } else {
            $crop = json_decode($crop, true);
            $cropWidth = (int)round($crop['width']);
            $cropHeight = (int)round($crop['height']);
            if ($cropWidth === 0 || $cropHeight === 0) {
                return [$width, $height];
            } else {
                return [$cropWidth, $cropHeight];
            }
        }
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return int
     */
    protected function calcRelativeHeight(int $width, int $height): int
    {
        return (int)($height / $width * 100);
    }

    /**
     * @param array $processInstruction
     *
     * @return string
     */
    protected function processImage(array $processInstruction): string
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var ImageService $imageService */
        $imageService = $objectManager->get(ImageService::class);
        $publicUrl = $imageService->applyProcessingInstructions(
            $this->fileReference,
            $processInstruction
        )->getPublicUrl();
        if (!is_string($publicUrl)) {
            return '';
        }

        return $publicUrl;
    }

    /**
     * @return string
     */
    protected function generateCrop(): string
    {
        $crop = json_decode($this->fileReference->getProperty('crop'), true);
        $crop = json_last_error() !== JSON_ERROR_NONE
            ? []
            : current($crop);


        $cropX = (float)$crop['cropArea']['x'] * $this->resourceWidth;
        $cropY = (float)$crop['cropArea']['y'] * $this->resourceHeight;
        $cropWidth = (float)$crop['cropArea']['width'];
        if ($cropWidth == 0) {
            $cropWidth = 1;
        }
        $cropWidth = $cropWidth * $this->resourceWidth;
        $cropHeight = (float)$crop['cropArea']['height'];
        if ($cropHeight == 0) {
            $cropHeight = 1;
        }
        $cropHeight = $cropHeight * $this->resourceHeight;

        if ($this->aspectRatioWidth == 0 || $this->aspectRatioHeight == 0) {
            return $this->createCropInstruction($cropX, $cropY, $cropWidth, $cropHeight);
        }

        /*
         * aspect ratio
         * 1 x height/width
         */
        $aspectRatio = $this->aspectRatioHeight / $this->aspectRatioWidth;
        $aspectRatioOfResource = $cropHeight / $cropWidth;

        if ($aspectRatioOfResource > $aspectRatio) {
            $newCropHeight = $cropWidth * $aspectRatio;
            $y = (($cropHeight - $newCropHeight) / 2) + $cropY;

            return $this->createCropInstruction($cropX, $y, $cropWidth, $newCropHeight);
        }
        if ($aspectRatioOfResource < $aspectRatio) {
            $newCropWidth = $cropHeight * (1 / $aspectRatio);
            $x = (($cropWidth - $newCropWidth) / 2) + $cropX;

            return $this->createCropInstruction($x, $cropY, $newCropWidth, $cropHeight);
        }

        return $this->createCropInstruction($cropX, $cropY, $cropWidth, $cropHeight);
    }

    /**
     * @param float $cropX
     * @param float $cropY
     * @param float $cropWidth
     * @param float $cropHeight
     *
     * @return string
     */
    protected function createCropInstruction(float $cropX, float $cropY, float $cropWidth, float $cropHeight): string
    {
        return json_encode([
            'x' => $cropX,
            'y' => $cropY,
            'width' => $cropWidth,
            'height' => $cropHeight,
            'rotate' => 0
        ]);
    }

    /**
     * @param string $crop
     * @param int $maxWidth
     *
     * @return array
     */
    protected function getProcessInstruction(string $crop, int $maxWidth = 0): array
    {
        return [
            'width' => null,
            'height' => null,
            'minWidth' => null,
            'minHeight' => null,
            'maxWidth' => $maxWidth,
            'maxHeight' => null,
            'crop' => $crop
        ];
    }

    /**
     * @param array $responsiveSizes
     *
     * @throws \Exception
     */
    protected function validateResponsiveSizes(array $responsiveSizes)
    {
        foreach ($responsiveSizes as $viewport => $size) {
            if (!is_numeric($size) || !is_numeric(substr($viewport, 0, -1)) ||
                strtolower(substr($viewport, -1)) !== 'w'
            ) {
                throw new \Exception('Invalid responsive sizes argument.');
            }
        }
    }
}
