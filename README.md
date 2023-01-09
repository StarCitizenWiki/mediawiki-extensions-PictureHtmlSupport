# ThumbnailerExt

`wfLoadExtension( 'ThumbnailerExt' )`

Add to `composer.local.json`:
```json
{
    "extra": {
        "merge-plugin": {
            "include": [
                "extensions/ThumbnailerExt/composer.json"
            ]
        }
    }
}
```

Run `composer install` in root