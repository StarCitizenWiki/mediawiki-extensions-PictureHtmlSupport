<?php

namespace MediaWiki\Extension\PictureHtmlSupport\Hook;

use ThumbnailImage;

interface PictureHtmlSupportBeforeProduceHtml
{
    public function onPictureHtmlSupportBeforeProduceHtml( ThumbnailImage $thumbnail, array &$sources ): void;
}