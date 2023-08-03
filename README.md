# PictureHtmlSupport

The PictureHtmlSupport extension wraps MediaWiki thumbnails on wiki page with `<picture>` elements and allow the user to add additional `<source>` elements within `<picture>`.

## Installation ##
1. Add the following line to `LocalSettings.php`:
    ```php
    wfLoadExtension( 'PictureHtmlSupport' );
    ```

2. Replace `includes/media/ThumbnailImage.php` with `includes/ThumbnailImage.php` from this extension.