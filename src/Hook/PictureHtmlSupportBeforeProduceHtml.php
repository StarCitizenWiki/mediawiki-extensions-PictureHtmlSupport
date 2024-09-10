<?php

namespace MediaWiki\Extension\PictureHtmlSupport\Hook;

use ThumbnailImage;

interface PictureHtmlSupportBeforeProduceHtml
{
    /**
     * Allows extensions to add <source> elements to a <picture> element
     *
     * @param  ThumbnailImage $thumbnail The thumbnail to add <source> element to
     * @param  array          &$sources  An array of array containing a 'srcset' and 'type' key. Optional keys are 'width' and 'height'
     * @return void
     */
    public function onPictureHtmlSupportBeforeProduceHtml( ThumbnailImage $thumbnail, array &$sources ): void;
}
