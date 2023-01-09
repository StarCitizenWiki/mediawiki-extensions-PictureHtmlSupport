# PictureHtmlSupport

The PictureHtmlSupport extension wraps MediaWiki thumbnails on wiki page with `<picture>` elements and allow the user to add additional `<source>` elements within `<picture>`.

## Installation ##
1. Add the following line to `LocalSettings.php`:
    ```php
    wfLoadExtension( 'PictureHtmlSupport' );
    ```

2. Add to `composer.local.json`:
    ```json
    {
        "extra": {
            "merge-plugin": {
                "include": [
                    "extensions/PictureHtmlSupport/composer.json"
                ]
            }
        }
    }
    ```
3. Run `composer install` in root
