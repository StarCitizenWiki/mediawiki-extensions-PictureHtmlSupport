<?php

/**
 * Base class for the output of file transformation methods.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Media
 */

// TODO: Replace with MediaWiki\Html\Html when dropping MW 1.39 support
use Html;
use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * Media transform output for images
 *
 * @ingroup Media
 */
class ThumbnailImage extends MediaTransformOutput
{
    /**
     * Get a thumbnail object from a file and parameters.
     * If $path is set to null, the output file is treated as a source copy.
     * If $path is set to false, no output file will be created.
     * $parameters should include, as a minimum, (file) 'width' and 'height'.
     * It may also include a 'page' parameter for multipage files.
     *
     * @param File              $file
     * @param string            $url        URL path to the thumb
     * @param string|null|false $path       Filesystem path to the thumb
     * @param array             $parameters Associative array of parameters
     */
    public function __construct( $file, $url, $path = false, $parameters = [] )
    {
        // Previous parameters:
        //   $file, $url, $width, $height, $path = false, $page = false

        $defaults = [
            'page' => false,
            'lang' => false
        ];

        if (is_array($parameters) ) {
            $actualParams = $parameters + $defaults;
        } else {
            // Using old format, should convert. Later a warning could be added here.
            $numArgs = func_num_args();
            $actualParams = [
                    'width' => $path,
                    'height' => $parameters,
                    'page' => ( $numArgs > 5 ) ? func_get_arg(5) : false
                ] + $defaults;
            $path = ( $numArgs > 4 ) ? func_get_arg(4) : false;
        }

        $this->file = $file;
        $this->url = $url;
        $this->path = $path;

        // These should be integers when they get here.
        // If not, there's a bug somewhere.  But let's at
        // least produce valid HTML code regardless.
        // @phan-suppress-next-line PhanTypeMismatchArgumentInternal Confused by old signature
        $this->width = (int)round($actualParams['width']);
        $this->height = (int)round($actualParams['height']);

        $this->page = $actualParams['page'];
        $this->lang = $actualParams['lang'];
    }

    /**
     * Return HTML <img ... /> tag for the thumbnail, will include
     * width and height attributes and a blank alt text (as required).
     *
     * @param array $options Associative array of options. Boolean options
     *                       should be indicated with a value of true for true, and false or
     *                       absent for false.
     *
     *                       alt          HTML alt attribute
     *                       title        HTML title attribute
     *                       desc-link    Boolean, show a description link
     *                       file-link    Boolean, show a file download link
     *                       valign       vertical-align property, if the output is an inline element
     *                       img-class    Class applied to the \<img\> tag, if there is such a tag
     *                       loading      Specify an explicit browser loading strategy for images and iframes.
     *                       desc-query   String, description link query params
     *                       override-width     Override width attribute. Should generally not set
     *                       override-height    Override height attribute. Should generally not set
     *                       no-dimensions      Boolean, skip width and height attributes (useful if
     *                       set in CSS)
     *                       custom-url-link    Custom URL to link to
     *                       custom-title-link  Custom Title object to link to
     *                       custom-title-link-query Querystring parameters array, for custom-title-link
     *                       custom-target-link Value of the target attribute, for custom-url-link
     *                       parser-extlink-*   Attributes added by parser for external links:
     *                       parser-extlink-rel: add rel="nofollow"
     *                       parser-extlink-target: link target, but overridden by custom-target-link
     *                       magnify-resource   To set the HTML resource attribute, when necessary
     *
     *                       For images, desc-link and file-link are implemented as a click-through. For
     *                       sounds and videos, they may be displayed in other ways.
     *
     * @return string
     */
    public function toHtml( $options = [] )
    {
        $services = MediaWikiServices::getInstance();
        $mainConfig = $services->getMainConfig();
        $nativeImageLazyLoading = $mainConfig->get(MainConfigNames::NativeImageLazyLoading);
        $enableLegacyMediaDOM = $mainConfig->get(MainConfigNames::ParserEnableLegacyMediaDOM);
        $enablePictureElement = $mainConfig->get('PictureHtmlSupportEnable');

        if (func_num_args() === 2 ) {
            throw new InvalidArgumentException(__METHOD__ . ' called in the old style');
        }

        $query = $options['desc-query'] ?? '';

        $attribs = [];

        // An empty alt indicates an image is not a key part of the content and
        // that non-visual browsers may omit it from rendering.  Only set the
        // parameter if it's explicitly requested.
        if (isset($options['alt']) ) {
            $attribs['alt'] = $options['alt'];
        }

        // Description links get the mw-file-description class and link
        // to the file description page, making the resource redundant
        if (!$enableLegacyMediaDOM 
            && isset($options['magnify-resource']) 
            && !( $options['desc-link'] ?? false )
        ) {
            $attribs['resource'] = $options['magnify-resource'];
        }

        $attribs += [
            'src' => $this->url,
            'decoding' => 'async',
        ];

        if ($options['loading'] ?? $nativeImageLazyLoading ) {
            $attribs['loading'] = $options['loading'] ?? 'lazy';
        }

        if (!empty($options['custom-url-link']) ) {
            $linkAttribs = [ 'href' => $options['custom-url-link'] ];
            if (!empty($options['title']) ) {
                $linkAttribs['title'] = $options['title'];
            }
            if (!empty($options['custom-target-link']) ) {
                $linkAttribs['target'] = $options['custom-target-link'];
            } elseif (!empty($options['parser-extlink-target']) ) {
                $linkAttribs['target'] = $options['parser-extlink-target'];
            }
            if (!empty($options['parser-extlink-rel']) ) {
                $linkAttribs['rel'] = $options['parser-extlink-rel'];
            }
        } elseif (!empty($options['custom-title-link']) ) {
            /**
 * @var Title $title 
*/
            $title = $options['custom-title-link'];
            $linkAttribs = [
                'href' => $title->getLinkURL($options['custom-title-link-query'] ?? null),
                'title' => empty($options['title']) ? $title->getPrefixedText() : $options['title']
            ];
        } elseif (!empty($options['desc-link']) ) {
            $linkAttribs = $this->getDescLinkAttribs(
                empty($options['title']) ? null : $options['title'],
                $query
            );
        } elseif (!empty($options['file-link']) ) {
            $linkAttribs = [ 'href' => $this->file->getUrl() ];
        } else {
            $linkAttribs = false;
            if (!empty($options['title']) ) {
                if ($enableLegacyMediaDOM ) {
                    $attribs['title'] = $options['title'];
                } else {
                    $linkAttribs = [ 'title' => $options['title'] ];
                }
            }
        }

        if (empty($options['no-dimensions']) ) {
            $attribs['width'] = $this->width;
            $attribs['height'] = $this->height;
        }
        if (!empty($options['valign']) ) {
            $attribs['style'] = "vertical-align: {$options['valign']}";
        }
        if (!empty($options['img-class']) ) {
            $attribs['class'] = $options['img-class'];
        }
        if (isset($options['override-height']) ) {
            $attribs['height'] = $options['override-height'];
        }
        if (isset($options['override-width']) ) {
            $attribs['width'] = $options['override-width'];
        }

        // Additional densities for responsive images, if specified.
        // If any of these urls is the same as src url, it'll be excluded.
        $responsiveUrls = array_diff($this->responsiveUrls, [ $this->url ]);
        if ($responsiveUrls ) {
            $attribs['srcset'] = Html::srcSet($responsiveUrls);
        }

        ( new HookRunner($services->getHookContainer()) )
            ->onThumbnailBeforeProduceHTML($this, $attribs, $linkAttribs);

        if ($enablePictureElement === false ) {
            return $this->linkWrap($linkAttribs, Xml::element('img', $attribs));
        }

        $sources = [];

        // Run PictureHtmlSupport hook
        MediaWikiServices::getInstance()->getHookContainer()->run(
            'PictureHtmlSupportBeforeProduceHtml',
            [
                $this,
                &$sources,
            ]
        );

        // Move srcset from img to source element
        if (isset($attribs[ 'srcset' ]) ) {
            $sources[] = [ 'srcset' => $this->url . ', ' . $attribs['srcset'] ];
            unset($attribs['srcset']);
        }

        $p = Html::openElement('picture');

        foreach ( $sources as $source ) {
            // <source> should always have a valid srcset when inside <picture>
            if (!$source['srcset'] ) {
                continue;
            }

            $sourceAttribs = [
                'srcset' => $source['srcset'],
            ];

            if (!empty($source['type']) ) {
                $sourceAttribs['type'] = $source['type'];
            }
            if (!empty($source['sizes']) ) {
                $sourceAttribs['sizes'] = $source['sizes'];
            }
            if (!empty($source['media']) ) {
                $sourceAttribs['media'] = $source['media'];
            }
            if (!empty($source['width']) && !empty($attribs['width']) && $source['width'] !== $attribs['width'] ) {
                $sourceAttribs['width'] = $source['width'];
            }
            if (!empty($source['height']) && !empty($attribs['height']) && $source['height'] !== $attribs['height'] ) {
                $sourceAttribs['height'] = $source['height'];
            }

            $p .= Html::element('source', $sourceAttribs);
        }

        // Original image
        $p .= Xml::element('img', $attribs);

        $p .= Html::closeElement('picture');

        $sourceLink = Html::rawElement(
            'a',
            [
                'href' => $this->file->getUrl(),
                'class' => 'mw-file-source',
                // FIXME: Need i18n
                'title' => 'View source image'
            ],
            '<!-- Image link for Crawlers -->'
        );

        return $this->linkWrap($linkAttribs, $p) . $sourceLink;
    }
}
