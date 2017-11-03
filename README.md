# ResponsiveImageRendering - TYPO3 Extension

Fluid-ViewHelper for rendering your images as responsive images.

## Syntax

#### fileReference
**Type**: \TYPO3\CMS\Core\Resource\FileReference<br/>
**Description**: File reference of the image to display.<br/>
**Required**: yes

#### responsiveSizes
**Type**: array<br/>
**Description**: The various sizes and the proper viewports.<br/>
**Required**: no<br/>
**Example**<br/>
```responsiveSizes="{768w: 768, 1024w: 1024, 1280w: 1280, 1920w: 1920}"```

#### defaultMaxWidth
**Type**: integer<br/>
**Description**: Maximum size of the default image without view-port.<br/>
**Required**: no

#### aspectRatio
**Type**: string (WxH, ex. 16x9)<br/>
**Description**: Crop image to defined aspect ratio. Every image will have the fix configured aspect ratio. The crop instructions from FAL entry will be fitted to the aspect ratio.<br/>
**Required**: no

#### width
**Type**: string<br/>
**Description**: Width of image.<br/>
**Required**: no<br/>
**Example**
```
width="100"
width="100px"
width="100%"
```

#### alt
**Type**: string<br/>
**Description**: Alternate text for image. The FAL entry alternate text has priority.<br/>
**Required**: no

#### title
**Type**: string<br/>
**Description**: Title for image. The FAL entry title has priority.<br/>
**Required**: no

#### plainCssClass
**Type**: boolean<br/>
**Description**: Output a css-class reference for the image. Can be used for background. Title, alt and width will be ignored, if set.<br/>
**Default**: false<br/>
**Required**: no

### class
**Type**: string<br/>
**Description**: Class attribute for the image.<br/>
**Required**: no

### style
**Type**: string<br/>
**Description**: Style attribute for the image.<br/>
**Required**: no

### absolute
**Type**: boolean<br/>
**Description**: Determines if the link to the image resources should be an absolute or relative path.<br/>
**Required**: no

## Examples
Something like this

    {namespace vf = ViktorFirus\ResponsiveImageRendering\ViewHelpers}
    
    <vf:image fileReference="{resource}"
               responsiveSizes="{768w: 768, 1024w: 1024, 1280w: 1280, 1920w: 1920}"
               defaultMaxWidth="750" width="50" aspectRatio="16x9" alt="Alternative"
               title="Title" />

output like this.

    <img src="fileadmin/_processed_/6/d/csm_****************_1afeb02e35.jpg"
         srcset="fileadmin/_processed_/6/d/csm_****************_1f40c49b96.jpg 768w,
             fileadmin/_processed_/6/d/csm_****************_dff6667f60.jpg 1024w,
             fileadmin/_processed_/6/d/csm_****************_e828fac558.jpg 1280w,
             fileadmin/_processed_/6/d/csm_****************_1ba51923e9.jpg 1920w"
             alt="Alternative" title="Title" width="50%">

Just add plainCssClass and remove attributes, who are ignored if clearCssClass is set.

    {namespace vf = ViktorFirus\ResponsiveImageRendering\ViewHelpers}

    <vf:image fileReference="{resource}"
               responsiveSizes="{768w: 768, 1024w: 1024, 1280w: 1280, 1920w: 1920}"
               defaultMaxWidth="750" aspectRatio="16x9" plainCssClass="1" />

This settings created css-class like this.

    fileadmin-****************-ddcfd1ba6ed9f23bdd5b1bf6d43b7f74
    
And in page header the definition of the css-class will be placed.