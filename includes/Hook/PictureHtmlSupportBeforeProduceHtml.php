<?php

namespace MediaWiki\Extension\PictureHtmlSupport\Hook;

use ThumbnailImage;

interface PictureHtmlSupportBeforeProduceHtml
{
    public function onPictureHtmlSupportBeforeProduceHtml( ThumbnailImage $image, array &$urls ): void;
}