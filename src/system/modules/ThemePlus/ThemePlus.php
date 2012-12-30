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

namespace InfinitySoft\ThemePlus;

use Template;
use FrontendTemplate;
use ThemePlus\Model\StylesheetModel;
use ThemePlus\Model\JavaScriptModel;
use Assetic\Contao\AsseticFactory;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Asset\StringAsset;
use Assetic\Asset\AssetCollection;

/**
 * Class ThemePlus
 *
 * Adding files to the page layout.
 */
class ThemePlus
    extends \Frontend
{
    /**
     * Singleton
     */
    private static $instance = null;


    /**
     * Get the singleton instance.
     *
     * @return \ThemePlus\ThemePlus
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ThemePlus();

            // remember cookie FE_PREVIEW state
            $fePreview = \Input::cookie('FE_PREVIEW');

            // set into preview mode
            \Input::setCookie('FE_PREVIEW',
                              true);

            // request the BE_USER_AUTH login status
            static::setDesignerMode(self::$instance->getLoginStatus('BE_USER_AUTH'));

            // restore previous FE_PREVIEW state
            \Input::setCookie('FE_PREVIEW',
                              $fePreview);
        }
        return self::$instance;
    }


    /**
     * If is in live mode.
     */
    protected $blnLiveMode = false;


    /**
     * Cached be login status.
     */
    protected $blnBeLoginStatus = null;


    /**
     * The variables cache.
     */
    protected $arrVariables = null;


    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }


    /**
     * Get productive mode status.
     */
    public static function isLiveMode()
    {
        return static::getInstance()->blnLiveMode
            ? true
            : false;
    }


    /**
     * Set productive mode.
     */
    public static function setLiveMode($liveMode = true)
    {
        static::getInstance()->blnLiveMode = $liveMode;
    }


    /**
     * Get productive mode status.
     */
    public static function isDesignerMode()
    {
        return static::getInstance()->blnLiveMode
            ? false
            : true;
    }


    /**
     * Set designer mode.
     */
    public static function setDesignerMode($designerMode = true)
    {
        static::getInstance()->blnLiveMode = !$designerMode;
    }


    /**
     * Calculate the target path for the asset.
     *
     * @param \Assetic\Asset\AssetInterface $asset
     * @param                               $suffix
     *
     * @return string
     */
    public static function getAssetPath(AssetInterface $asset, $suffix)
    {
        $filters = array();
        foreach ($asset->getFilters() as $v) {
            $filters[] = get_class($v);
        }
        $filters = '[' . implode(',',
                                 $filters) . ']';

        // calculate path for collections
        if ($asset instanceof AssetCollection) {
            $string = $filters;
            foreach ($asset->all() as $child) {
                $string .= '-' . static::getAssetPath($child,
                                                      $suffix);
            }
            return 'assets/css/' . substr(md5($string),
                                          0,
                                          8) . '-collection.' . $suffix;
        }

        // calculate cache path from content
        else if ($asset instanceof StringAsset) {
            return 'assets/css/' . substr(md5($filters . '-' . $asset->getContent() . '-' . $asset->getLastModified()),
                                          0,
                                          8) . '-' . basename($asset->getSourcePath()) . '.' . $suffix;
        }

        // calculate cache path from source path
        else {
            return 'assets/css/' . substr(md5($filters . '-' . $asset->getSourcePath() . '-' . $asset->getLastModified()),
                                          0,
                                          8) . '-' . basename($asset->getSourcePath(),
                                                              '.' . $suffix) . '.' . $suffix;
        }
    }

    /**
     * Store an asset.
     *
     * @param \Assetic\Asset\AssetInterface $asset
     * @param                               $suffix
     *
     * @return string
     */
    public static function storeAsset(AssetInterface $asset, $suffix, $additionalFilters = null)
    {
        $path = static::getAssetPath($asset,
                                     $suffix);
        $asset->setTargetPath($path);

        if (!file_exists(TL_ROOT . '/' . $path)) {

            $file = new \File($path);
            $file->write($asset->dump($additionalFilters
                                          ? new \Assetic\Filter\FilterCollection($additionalFilters)
                                          : null));
            $file->close();
        }

        return $path;
    }

    /**
     * Check the file browser filter settings against the request browser.
     *
     * @param \Model $file
     *
     * @return bool
     */
    public static function checkBrowserFilter(\Model\Collection $file)
    {
        // TODO
        return true;
    }

    /**
     * Generate a debug comment from an asset.
     *
     * @return string
     */
    public static function getDebugComment(AssetInterface $asset)
    {
        if ($GLOBALS['TL_CONFIG']['debugMode'] || ThemePlus::isDesignerMode()) {
            return '<!-- ' . static::getAssetDebugString($asset) . ' -->' . "\n";
        }
        return '';
    }

    /**
     * Generate a debug string for the asset.
     *
     * @param \Assetic\Asset\AssetInterface $asset
     * @param string                        $depth
     *
     * @return string
     */
    public static function getAssetDebugString(AssetInterface $asset, $depth = '')
    {
        $filters = array();
        foreach ($asset->getFilters() as $v) {
            $filters[] = get_class($v);
        }

        if ($asset instanceof AssetCollection) {
            /** @var AssetCollection $asset */
            $string = 'collection { ' . 'target path: ' . $asset->getTargetPath() . ', ' . 'filters: [' . implode(', ',
                                                                                                                  $filters) . '], ' . 'last modified: ' . $asset->getLastModified();

            foreach ($asset->all() as $child) {
                $string .= "\n" . $depth . '- ' . static::getAssetDebugString($child,
                                                                              $depth . '    ');
            }

            $string .= ' }';
            return $string;
        }

        else {
            return 'asset { ' . 'source path: ' . $asset->getSourcePath() . ', ' . 'target path: ' . $asset->getTargetPath() . ', ' . 'filters: [' . implode(', ',
                                                                                                                                                             $filters) . '], ' . 'last modified: ' . $asset->getLastModified() . ' }';
        }
    }

    /**
     * Wrap the conditional comment around.
     *
     * @param string $html The html to wrap around.
     * @param string $cc   The cc that should wrapped.
     *
     * @return string
     */
    public static function wrapCc($html, $cc)
    {
        if (strlen($cc)) {
            return '<!--[if ' . $cc . ']>' . $html . '<![endif]-->';
        }
        return $html;
    }

    /**
     * Strip static urls.
     */
    public static function stripStaticURL($strUrl)
    {
        if (defined('TL_ASSETS_URL') && strlen(TL_ASSETS_URL) > 0 && strpos($strUrl,
                                                                            TL_ASSETS_URL) === 0
        ) {
            return substr($strUrl,
                          strlen(TL_ASSETS_URL));
        }
        return $strUrl;
    }

    /**
     * Detect gzip data end decode it.
     *
     * @param mixed $varData
     */
    public function decompressGzip($varData)
    {
        if ($varData[0] == 31 && $varData[0] == 139 && $varData[0] == 8
        ) {
            return gzdecode($varData);
        }
        else {
            return $varData;
        }
    }


    /**
     * Handle @charset and remove the rule.
     */
    public function handleCharset($strContent)
    {
        if (preg_match('#\@charset\s+[\'"]([\w\-]+)[\'"]\;#Ui',
                       $strContent,
                       $arrMatch)
        ) {
            // convert character encoding to utf-8
            if (strtoupper($arrMatch[1]) != 'UTF-8') {
                $strContent = iconv(strtoupper($arrMatch[1]),
                                    'UTF-8',
                                    $strContent);
            }
            // remove all @charset rules
            $strContent = preg_replace('#\@charset\s+.*\;#Ui',
                                       '',
                                       $strContent);
        }
        return $strContent;
    }

    /**
     * @see \Contao\Template::parse
     *
     * @param \Template $template
     */
    public function hookParseTemplate(Template $template)
    {
        if ($template instanceof FrontendTemplate) {
            if (substr($template->getName(), 0, 3) == 'fe_') {
                $template->mootools = '[[TL_THEME_PLUS]]' . "\n" . $template->mootools;
            }
        }
    }

    /**
     * @see \Contao\Controller::replaceDynamicScriptTags
     *
     * @param $strBuffer
     */
    public function hookReplaceDynamicScriptTags($strBuffer)
    {
        global $objPage;

        if ($objPage) {
            // search for the layout
            $layout = \LayoutModel::findByPk($objPage->layout);

            if ($layout) {
                // build exclude list
                if (!is_array($GLOBALS['TL_THEME_EXCLUDE'])) {
                    $GLOBALS['TL_THEME_EXCLUDE'] = array();
                }
                if (!is_array($layout->theme_plus_exclude_files)) {
                    $layout->theme_plus_exclude_files = deserialize($layout->theme_plus_exclude_files,
                                                                    true);
                }
                if (count($layout->theme_plus_exclude_files) > 0) {
                    foreach ($layout->theme_plus_exclude_files as $v) {
                        if ($v[0]) {
                            $GLOBALS['TL_THEME_EXCLUDE'][] = $v[0];
                        }
                    }
                }

                // the search and replace array
                $sr = array();

                // parse stylesheets
                $this->parseStylesheets($layout,
                                        $sr);

                // parse javascripts
                $this->parseJavaScripts($layout,
                                        $sr);

                // replace dynamic scripts
                return str_replace(array_keys($sr),
                                   array_values($sr),
                                   $strBuffer);
            }
        }

        return $strBuffer;
    }

    /**
     * Parse all stylesheets and add them to the search and replace array.
     *
     * @param \LayoutModel $layout
     * @param array        $sr The search and replace array.
     *
     * @return mixed
     */
    protected function parseStylesheets(\LayoutModel $layout, array &$sr)
    {
        global $objPage;

        // html mode
        $xhtml     = ($objPage->outputFormat == 'xhtml');
        $tagEnding = $xhtml
            ? ' />'
            : '>';

        // default filter
        $defaultFilters = AsseticFactory::createFilterOrChain($layout->asseticStylesheetFilter,
                                                              static::isDesignerMode());

        // list of non-static stylesheets
        $stylesheets = array();

        // collection of static stylesheets
        $collection = new AssetCollection(array(), array(), TL_ROOT);

        // Add the CSS framework style sheets
        if (is_array($GLOBALS['TL_FRAMEWORK_CSS']) && !empty($GLOBALS['TL_FRAMEWORK_CSS'])) {
            $this->addAssetsToCollectionFromArray(array_unique($GLOBALS['TL_FRAMEWORK_CSS']),
	                                              'css',
                                                  null,
                                                  $collection,
                                                  $stylesheets);
        }
        $GLOBALS['TL_FRAMEWORK_CSS'] = array();

        // Add the internal style sheets
        if (is_array($GLOBALS['TL_CSS']) && !empty($GLOBALS['TL_CSS'])) {
            $this->addAssetsToCollectionFromArray(array_unique($GLOBALS['TL_CSS']),
	                                              'css',
                                                  true,
                                                  $collection,
                                                  $stylesheets);
        }
        $GLOBALS['TL_CSS'] = array();

        // Add the user style sheets
        if (is_array($GLOBALS['TL_USER_CSS']) && !empty($GLOBALS['TL_USER_CSS'])) {
            $this->addAssetsToCollectionFromArray(array_unique($GLOBALS['TL_USER_CSS']),
	                                              'css',
                                                  true,
                                                  $collection,
                                                  $stylesheets);
        }
        $GLOBALS['TL_USER_CSS'] = array();

        // Add layout files
        $stylesheet = StyleSheetModel::findByPks(deserialize($layout->theme_plus_stylesheets,
                                                             true),
                                                 array('order' => 'sorting'));
        $this->addAssetsToCollectionFromDatabase($stylesheet,
                                                 'css',
                                                 $collection,
                                                 $stylesheets);

        // Add files from page tree
        $this->addAssetsToCollectionFromPageTree($objPage,
                                                 'stylesheets',
                                                 'ThemePlus\Model\StylesheetModel',
                                                 $collection,
                                                 $stylesheets,
                                                 true);

        // string contains the scripts include code
        $scripts = '';

        // add collection to list
        if (count($collection->all())) {
            $stylesheets[] = array('asset' => $collection);
        }

        // add files
        foreach ($stylesheets as $stylesheet) {
	        // use proxy for development
	        if (static::isDesignerMode() && isset($stylesheet['id'])) {
		        $url = 'system/modules/ThemePlus/web/proxy.php?page=' . $GLOBALS['objPage']->id . '&source=' . $stylesheet['id'];
	        }

            // use asset
            else if (isset($stylesheet['asset'])) {
                $url = static::storeAsset($stylesheet['asset'],
                                          'css',
                                          $defaultFilters);
	            $url = static::addStaticUrlTo($url);
            }

            // use url
            else if (isset($stylesheet['url'])) {
                $url = $stylesheet['url'];
	            $url = static::addStaticUrlTo($url);
            }

            // continue if file have no source
            else {
                continue;
            }

            // generate html
            $html = '<link' . ($xhtml
                ? ' type="text/css"'
                : '') . ' rel="stylesheet" href="' . $url . '"' . ((isset($stylesheet['media']) && $stylesheet['media'] != 'all')
                ? ' media="' . $stylesheet['media'] . '"'
                : '') . $tagEnding;

            // wrap cc around
            $html = static::wrapCc($html,
                                   $stylesheet['cc']) . "\n";

            // add debug information
            if (static::isDesignerMode()) {
                // use asset
                if (isset($stylesheet['asset'])) {
                    $html = static::getDebugComment($stylesheet['asset']) . $html;
                }

                // use url
                else if (isset($stylesheet['url'])) {
                    $html = '<!-- url { ' . $stylesheet['url'] . ' } -->' . "\n" . $html;
                }
            }

            $scripts .= $html;
        }

        $scripts .= '[[TL_CSS]]';
        $sr['[[TL_CSS]]'] = $scripts;
    }

    /**
     * Parse all javascripts and add them to the search and replace array.
     *
     * @param \LayoutModel $layout
     * @param array        $sr The search and replace array.
     *
     * @return mixed
     */
    protected function parseJavaScripts(\LayoutModel $layout, array &$sr)
    {
        global $objPage;

        // html mode
        $xhtml = ($objPage->outputFormat == 'xhtml');

        // default filter
        $defaultFilters = AsseticFactory::createFilterOrChain($layout->asseticJavaScriptFilter,
                                                              static::isDesignerMode());

        // list of non-static javascripts
        $javascripts = array();

        // collection of static javascript
        $collection = new AssetCollection(array(), array(), TL_ROOT);

        // Add the internal scripts
        if (is_array($GLOBALS['TL_JAVASCRIPT']) && !empty($GLOBALS['TL_JAVASCRIPT'])) {
            $this->addAssetsToCollectionFromArray($GLOBALS['TL_JAVASCRIPT'],
	                                              'js',
                                                  false,
                                                  $collection,
                                                  $javascripts,
                                                  $layout->theme_plus_default_javascript_position);
        }
        $GLOBALS['TL_JAVASCRIPT'] = array();

        // Add layout files
        $javascript = JavaScriptModel::findByPks(deserialize($layout->theme_plus_javascripts,
                                                             true),
                                                 array('order' => 'sorting'));
        $this->addAssetsToCollectionFromDatabase($javascript,
                                                 'js',
                                                 $collection,
                                                 $javascripts,
                                                 $layout->theme_plus_default_javascript_position);

        // Add files from page tree
        $this->addAssetsToCollectionFromPageTree($objPage,
                                                 'javascripts',
                                                 'ThemePlus\Model\JavaScriptModel',
                                                 $collection,
                                                 $javascripts,
                                                 true,
                                                 $layout->theme_plus_default_javascript_position);

        // string contains the scripts include code
        $head = '';
        $body = '';

        // add collection to list
        if (count($collection->all())) {
            $javascripts[] = array('asset'    => $collection,
                                   'position' => $layout->theme_plus_default_javascript_position);
        }

        // add files
        foreach ($javascripts as $javascript) {
	        // use proxy for development
	        if (static::isDesignerMode() && isset($javascript['id'])) {
		        $url = 'system/modules/ThemePlus/web/proxy.php?page=' . $GLOBALS['objPage']->id . '&source=' . $javascript['id'];
	        }

            // use asset
            else if (isset($javascript['asset'])) {
                $url = static::storeAsset($javascript['asset'],
                                          'js',
                                          $defaultFilters);
	            $url = static::addStaticUrlTo($url);
            }

            // use url
            else if (isset($javascript['url'])) {
                $url = $javascript['url'];
	            $url = static::addStaticUrlTo($url);
            }

            // continue if file have no source
            else {
                continue;
            }

            // generate html
            if ($layout->theme_plus_javascript_lazy_load) {
                $html = '<script' . ($xhtml
                    ? ' type="text/javascript"'
                    : '') . '>window.loadAsync(' . json_encode($url) . ');</script>';
            }
            else {
                $html = '<script' . ($xhtml
                    ? ' type="text/javascript"'
                    : '') . ' src="' . $url . '"></script>';
            }

            // wrap cc
            $html = static::wrapCc($html,
                                   $javascript['cc']) . "\n";

            // add debug information
            if (static::isDesignerMode()) {
                if (isset($javascript['asset'])) {
                    $html = static::getDebugComment($javascript['asset']) . $html;
                }
                else if (isset($javascript['url'])) {
                    $html = '<!-- url { ' . $javascript['url'] . ' } -->' . "\n" . $html;
                }
            }

            if (isset($javascript['position']) && $javascript['position'] == 'body') {
                $body .= $html;
            }
            else {
                $head .= $html;
            }
        }

        // add async.js script
        if ($layout->theme_plus_javascript_lazy_load) {
            $async = new FileAsset(TL_ROOT . '/system/modules/ThemePlus/assets/js/async.js', $defaultFilters);
            $async = '<script' . ($xhtml
                ? ' type="text/javascript"'
                : '') . '>' . $async->dump() . '</script>' . "\n";

            if ($head) {
                $head = $async . $head;
            }
            else if ($body) {
                $body = $async . $body;
            }
        }

        $head .= '[[TL_HEAD]]';
        $sr['[[TL_HEAD]]'] = $head;

        $sr['[[TL_THEME_PLUS]]'] = $body;
    }

    protected function addAssetsToCollectionFromArray(array $sources,
	                                                  $type,
                                                      $split,
                                                      AssetCollection $collection,
                                                      array &$array,
                                                      $position = 'head')
    {
        foreach ($sources as $source) {
            if ($source instanceof AssetInterface) {
	            if (static::isLiveMode()) {
                    $collection->add($source);
	            }
	            else if ($source instanceof StringAsset) {
		            $data = base64_encode($source->dump());

		            $array[] = array(
			            'id' => $type . ':' . 'base64:' . $data,
			            'asset' => $source,
	                    'position' => $position
		            );
	            }
	            else if ($source instanceof FileAsset) {
		            $reflectionClass = new ReflectionClass($source);
		            $sourceProperty = $reflectionClass->getProperty('source');
		            $sourceProperty->setAccessible(true);
		            $sourcePath = $sourceProperty->getValue();

		            $array[] = array(
			            'id' => $type . ':' . $sourcePath,
			            'asset' => $source,
	                    'position' => $position
		            );
	            }
	            else {
		            $array[] = array(
			            'asset' => $source,
	                    'position' => $position
		            );
	            }
                continue;
            }

            if ($split === null) {
                // use source as source
            }
            else if ($split === true) {
                list($source, $media, $mode) = explode('|',
                                                       $source);
            }
            else if ($split === false) {
                list($source, $mode) = explode('|',
                                               $source);
            }
            else {
                return;
            }

            // remove static url
            $source = static::stripStaticURL($source);

            // skip file
            if (in_array($source,
                         $GLOBALS['TL_THEME_EXCLUDE'])
            ) {
                continue;
            }

            // if stylesheet is an absolute url...
            if (preg_match('#^\w:#',
                           $source)
            ) {
                // ...fetch the stylesheet
                if ($mode == 'static' && static::isLiveMode()) {
                    $asset = new HttpAsset($source);
                }
                // ...or add if it is not static
                else {
                    $array[] = array(
	                    'url' => $source,
	                    'media' => $media
                    );
                    continue;
                }
            }
            else {
                $asset = new FileAsset(TL_ROOT . '/' . $source, array(), TL_ROOT, $source);
            }

            if (($mode == 'static' || $mode === null) && static::isLiveMode()) {
                $collection->add($asset);
            }
            else {
                $array[] = array(
	                'id' => $type . ':' . $source,
	                'asset' => $asset,
	                'media' => $media,
	                'position' => $position
                );
            }
        }
    }

    protected function addAssetsToCollectionFromDatabase(\Model\Collection $data,
                                                         $type,
                                                         AssetCollection $collection,
                                                         array &$array,
                                                         $position = 'head')
    {
        if ($data) {
            while ($data->next()) {
                if (static::checkBrowserFilter($data)) {
                    $asset  = null;
                    $filter = array();

                    if ($data->asseticFilter) {
                        $temp = AsseticFactory::createFilterOrChain($data->asseticFilter,
                                                                    static::isDesignerMode());
                        if ($temp) {
                            $filter = array($temp);
                        }
                    }

                    if ($data->position) {
                        $position = $data->position;
                    }

                    switch ($data->type) {
                    case 'code':
                        $asset = new StringAsset($data->code, $filter, TL_ROOT, 'assets/' . $type . '/' . $data->code_snippet_title . '.' . $type);
                        $asset->setLastModified($data->tstamp);
                        break;

                    case 'url':
                        // skip file
                        if (in_array($data->url,
                                     $GLOBALS['TL_THEME_EXCLUDE'])
                        ) {
                            break;
                        }

                        if ($data->fetchUrl) {
                            $asset = new HttpAsset($data->url, $filter);
                        }
                        else {
                            $array[] = array('url'   => $data->url, 'media' => $data->media, 'cc'    => $data->cc);
                        }
                        break;

                    case 'file':
                        $file = \FilesModel::findByPk($data->file);

                        if ($file) {
                            // skip file
                            if (in_array($file->path,
                                         $GLOBALS['TL_THEME_EXCLUDE'])
                            ) {
                                break;
                            }

                            $asset = new FileAsset(TL_ROOT . '/' . $file->path, $filter, TL_ROOT, $file->path);
                        }
                        break;
                    }

                    if ($asset) {
                        if (static::isLiveMode()) {
                            $collection->add($asset);
                        }
                        else {
                            $array[] = array(
	                            'id' => $type . ':' . $data->id,
	                            'asset' => $asset,
	                            'position' => $position
                            );
                        }
                    }
                }
            }
        }
    }

    protected function addAssetsToCollectionFromPageTree($objPage,
                                                         $type,
                                                         $model,
                                                         AssetCollection $collection,
                                                         array &$array,
                                                         $local = false,
                                                         $position = 'head')
    {
        // inherit from parent page
        if ($objPage->pid) {
            $objParent = $this->getPageDetails($objPage->pid);
            $this->addAssetsToCollectionFromPageTree($objParent,
                                                     $type,
                                                     $model,
                                                     $collection,
                                                     $array,
                                                     false,
                                                     $position);
        }

        // add local (not inherited) files
        if ($local) {
            $trigger = 'theme_plus_include_' . $type . '_noinherit';

            if ($objPage->$trigger) {
                $key = 'theme_plus_' . $type . '_noinherit';

                $data = call_user_func(array($model, 'findByPks'),
                                       deserialize($objPage->$key,
                                                   true),
                                       array('order' => 'sorting'));
                $this->addAssetsToCollectionFromDatabase($data,
                                                         $type == 'stylesheets'
                                                             ? 'css'
                                                             : 'js',
                                                         $collection,
                                                         $array,
                                                         $position);
            }
        }

        // add inherited files
        $trigger = 'theme_plus_include_' . $type;

        if ($objPage->$trigger) {
            $key = 'theme_plus_' . $type;

            $data = call_user_func(array($model, 'findByPks'),
                                   deserialize($objPage->$key,
                                               true),
                                   array('order' => 'sorting'));
            $this->addAssetsToCollectionFromDatabase($data,
                                                     $type == 'stylesheets'
                                                         ? 'css'
                                                         : 'js',
                                                     $collection,
                                                     $array,
                                                     $position);
        }
    }

    /**
     * Render a variable to css code.
     */
    public function renderVariable(\ThemePlus\Model\VariableModel $variable)
    {
        // HOOK: create framework code
        if (isset($GLOBALS['TL_HOOKS']['renderVariable']) && is_array($GLOBALS['TL_HOOKS']['renderVariable'])) {
            foreach ($GLOBALS['TL_HOOKS']['renderVariable'] as $callback) {
                $this->import($callback[0]);
                $varResult = $this->$callback[0]->$callback[1]($variable);
                if ($varResult !== false) {
                    return $varResult;
                }
            }
        }

        switch ($variable->type) {
        case 'text':
            return $variable->text;

        case 'url':
            return sprintf('url("%s")',
                           str_replace('"',
                                       '\\"',
                                       $variable->url));

        case 'file':
            return sprintf('url("../../%s")',
                           str_replace('"',
                                       '\\"',
                                       $variable->file));

        case 'color':
            return '#' . $variable->color;

        case 'size':
            $arrSize       = deserialize($variable->size);
            $arrTargetSize = array();
            foreach (array('top', 'right', 'bottom', 'left') as $k) {
                if (strlen($arrSize[$k])) {
                    $arrTargetSize[] = $arrSize[$k] . $arrSize['unit'];
                }
                else {
                    $arrTargetSize[] = '';
                }
            }
            while (count($arrTargetSize) > 0 && empty($arrTargetSize[count($arrTargetSize) - 1])) {
                array_pop($arrTargetSize);
            }
            foreach ($arrTargetSize as $k=> $v) {
                if (empty($v)) {
                    $arrTargetSize[$k] = '0';
                }
            }
            return implode(' ',
                           $arrTargetSize);
        }
    }


    /**
     * Get the variables.
     */
    public function getVariables($varTheme, $strPath = false)
    {
        $objTheme = $this->findTheme($varTheme);

        if (!isset($this->arrVariables[$objTheme->id])) {
            $this->arrVariables[$objTheme->id] = array();

            $objVariable = $this->Database
                ->prepare("SELECT * FROM tl_theme_plus_variable WHERE pid=?")
                ->execute($objTheme->id);

            while ($objVariable->next()) {
                $this->arrVariables[$objTheme->id][$objVariable->name] = $this->renderVariable($objVariable,
                                                                                               $strPath);
            }
        }

        return $this->arrVariables[$objTheme->id];
    }


    /**
     * Replace variables.
     */
    public function replaceVariables($strCode, $arrVariables = false, $strPath = false)
    {
        if (!$arrVariables) {
            $arrVariables = $this->getVariables(false,
                                                $strPath);
        }
        $objVariableReplace = new VariableReplacer($arrVariables);
        return preg_replace_callback('#\$([[:alnum:]_\-]+)#',
                                     array(&$objVariableReplace, 'replace'),
                                     $strCode);
    }


    /**
     * Replace variables.
     */
    public function replaceVariablesByTheme($strCode, $varTheme, $strPath = false)
    {
        $objVariableReplace = new VariableReplacer($this->getVariables($varTheme,
                                                                       $strPath));
        return preg_replace_callback('#\$([[:alnum:]_\-]+)#',
                                     array(&$objVariableReplace, 'replace'),
                                     $strCode);
    }


    /**
     * Replace variables.
     */
    public function replaceVariablesByLayout($strCode, $varLayout, $strPath = false)
    {
        $objVariableReplace = new VariableReplacer($this->getVariables($this->findThemeByLayout($varLayout),
                                                                       $strPath));
        return preg_replace_callback('#\$([[:alnum:]_\-]+)#',
                                     array(&$objVariableReplace, 'replace'),
                                     $strCode);
    }


    /**
     * Calculate a variables hash.
     */
    public function getVariablesHash($arrVariables)
    {
        $strVariables = '';
        foreach ($arrVariables as $k=> $v) {
            $strVariables .= $k . ':' . $v . "\n";
        }
        return md5($strVariables);
    }


    /**
     * Calculate a variables hash.
     */
    public function getVariablesHashByTheme($varTheme)
    {
        return $this->getVariablesHash($this->getVariables($varTheme));
    }


    /**
     * Calculate a variables hash.
     */
    public function getVariablesHashByLayout($varLayout)
    {
        return $this->getVariablesHash($this->getVariables($this->findThemeByLayout($varLayout)));
    }


    /**
     * Check the file filter.
     *
     * @return Return true if the file filter NOT match, otherwise false.
     */
    public function filter($objFile)
    {
        if ($objFile->filter) {
            $ua      = $this->Environment->agent;
            $arrRule = deserialize($objFile->filterRule,
                                   true);
            foreach ($arrRule as $strRule) {
                if (preg_match('#^os-(.*)$#',
                               $strRule,
                               $m)
                ) {
                    if ($ua->os == $m[1]) {
                        return $objFile->filterInvert
                            ? true
                            : false;
                    }
                }
                else if (preg_match('#^browser-(.*?)(?:-(\d+))?$#',
                                    $strRule,
                                    $m)
                ) {
                    if ($ua->browser == $m[1] && (empty($m[2]) || $ua->version == floatval($m[2]))) {
                        return $objFile->filterInvert
                            ? true
                            : false;
                    }
                }
                else if ($strRule == '@mobile') {
                    if ($ua->mobile) {
                        return $objFile->filterInvert
                            ? true
                            : false;
                    }
                }
            }

            return $objFile->filterInvert
                ? false
                : true;
        }
        return false;
    }


    /**
     * Wrap a javascript src for lazy include.
     *
     * @return string
     */
    public function wrapJavaScriptLazyInclude($strSrc)
    {
        return 'loadAsync(' . json_encode($strSrc) . ');';
    }


    /**
     * Wrap a javascript src for lazy embedding.
     *
     * @return string
     */
    public function wrapJavaScriptLazyEmbedded($strSource)
    {
        $strBuffer = 'var f=(function(){';
        $strBuffer .= $strSource;
        $strBuffer .= '});';
        $strBuffer .= 'if (window.attachEvent){';
        $strBuffer .= 'window.attachEvent("onload",f);';
        $strBuffer .= '}else{';
        $strBuffer .= 'window.addEventListener("load",f,false);';
        $strBuffer .= '}';
        return $strBuffer;
    }


    /**
     * Generate the html code.
     *
     * @param array  $arrFileIds
     * @param bool   $blnAbsolutizeUrls
     * @param object $objAbsolutizePage
     *
     * @return string
     */
    public function includeFiles($arrFileIds,
                                 $blnAggregate = null,
                                 $blnAbsolutizeUrls = false,
                                 $objAbsolutizePage = null)
    {
        $arrResult = array();

        // add css files
        $arrFiles = $this->getCssFiles($arrFileIds,
                                       $blnAggregate,
                                       $blnAbsolutizeUrls,
                                       $objAbsolutizePage);
        foreach ($arrFiles as $objFile) {
            $arrResult[] = $objFile->getIncludeHtml();
        }

        // add javascript files
        $arrFiles = $this->getJavaScriptFiles($arrFileIds);
        foreach ($arrFiles as $objFile) {
            $arrResult[] = $objFile->getIncludeHtml();
        }
        return $arrResult;
    }


    /**
     * Generate the html code.
     *
     * @param array $arrFileIds
     *
     * @return array
     */
    public function embedFiles($arrFileIds, $blnAggregate = null, $blnAbsolutizeUrls = false, $objAbsolutizePage = null)
    {
        $arrResult = array();

        // add css files
        $arrFiles = $this->getCssFiles($arrFileIds,
                                       $blnAbsolutizeUrls,
                                       $objAbsolutizePage);
        foreach ($arrFiles as $objFile) {
            $arrResult[] = $objFile->getEmbeddedHtml();
        }

        // add javascript files
        $arrFiles = $this->getJavaScriptFiles($arrFileIds);
        foreach ($arrFiles as $objFile) {
            $arrResult[] = $objFile->getEmbeddedHtml();
        }
        return $arrResult;
    }


    /**
     * Hook
     *
     * @param string $strTag
     *
     * @return mixed
     */
    public function hookReplaceInsertTags($strTag)
    {
        $arrParts = explode('::',
                            $strTag);
        $arrIds   = explode(',',
                            $arrParts[1]);
        switch ($arrParts[0]) {
        case 'include_theme_file':
            return implode("\n",
                           $this->includeFiles($arrIds)) . "\n";

        case 'embed_theme_file':
            return implode("\n",
                           $this->embedFiles($arrIds)) . "\n";

            // @deprecated
        case 'insert_additional_sources':
            return implode("\n",
                           $this->includeFiles($arrIds)) . "\n";

            // @deprecated
        case 'include_additional_sources':
            return implode("\n",
                           $this->embedFiles($arrIds)) . "\n";
        }

        return false;
    }


    /**
     * Helper function that filter out all non integer values.
     */
    public function filter_int($string)
    {
        if (is_numeric($string)) {
            return true;
        }
        return false;
    }


    /**
     * Helper function that filter out all integer values.
     */
    public function filter_string($string)
    {
        if (is_numeric($string)) {
            return false;
        }
        return true;
    }
}


/**
 * Sorting helper.
 */
class SortingHelper
{
    /**
     * Sorted array of ids and paths.
     */
    protected $arrSortedIds;


    /**
     * Constructor
     */
    public function __construct($arrSortedIds)
    {
        $this->arrSortedIds = array_values($arrSortedIds);
    }


    /**
     * uksort callback
     */
    public function cmp($a, $b)
    {
        $a = array_search($a,
                          $this->arrSortedIds);
        $b = array_search($b,
                          $this->arrSortedIds);

        // both are equals or not found
        if ($a === $b) {
            return 0;
        }

        // $a not found
        if ($a === false) {
            return -1;
        }

        // $b not found
        if ($b === false) {
            return 1;
        }

        return $a - $b;
    }
}


/**
 * A little helper class that work as callback for preg_replace_callback.
 */
class VariableReplacer
    extends \System
{
    /**
     * The variables and there values.
     */
    protected $variables;


    /**
     * Constructor
     */
    public function __construct($variables)
    {
        $this->variables = $variables;
    }


    /**
     * Callback function for preg_replace_callback.
     * Searching the variable in $this->variables and return the value
     * or a comment, that the variable does not exists!
     */
    public function replace($m)
    {
        if (isset($this->variables[$m[1]])) {
            return $this->variables[$m[1]];
        }

        // HOOK: replace undefined variable
        if (isset($GLOBALS['TL_HOOKS']['replaceUndefinedVariable']) && is_array($GLOBALS['TL_HOOKS']['replaceUndefinedVariable'])) {
            foreach ($GLOBALS['TL_HOOKS']['replaceUndefinedVariable'] as $callback) {
                $this->import($callback[0]);
                $varResult = $this->$callback[0]->$callback[1]($m[1]);
                if ($varResult !== false) {
                    return $varResult;
                }
            }
        }

        return $m[0];
    }
}
