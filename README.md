# PictureHtmlSupport

The PictureHtmlSupport enhances MediaWiki thumbnails on wiki page with `<picture>` elements. It does these things:
1. Wrap thumbnail `<img>` element with `<picture>` element
2. Move resposive image definition to `<source>` element if enabled
3. Introduce the `PictureHtmlSupportBeforeProduceHtml` hook for users to add additional `<source>` elements
4. Add a hidden `<a>` element next to the thumbnail HTML so web crawlers can crawl the original resolution image (https://phabricator.wikimedia.org/T54647)

## Installation ##
1. Add the following line to `LocalSettings.php`:
    ```php
    wfLoadExtension( 'PictureHtmlSupport' );
    ```

2. Replace `includes/media/ThumbnailImage.php` (make a backup!) with `includes/ThumbnailImage.php` from this extension.
