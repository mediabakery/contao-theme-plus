<?php

/**
 * Theme+ - Theme extension for the Contao Open Source CMS
 *
 * Copyright (C) 2012 InfinitySoft <http://www.infinitysoft.de>
 *
 * @package    Theme+
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @link       http://www.themeplus.de
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace InfinitySoft\ThemePlus\Filter;

/**
 * Class PageRegular
 */
class ContaoInsertTagFilter
    extends \Controller
    implements \Assetic\Filter\FilterInterface
{
    /**
     * Filters an asset after it has been loaded.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterLoad(\Assetic\Asset\AssetInterface $asset)
    {
        // get asset content
        $content = $asset->getContent();

        // replace all insert tags
        $content = $this->replaceInsertTags($content,
                                            false);

        // set asset content
        $asset->setContent($content);
    }

    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(\Assetic\Asset\AssetInterface $asset)
    {
    }
}
