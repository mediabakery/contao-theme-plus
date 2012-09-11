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


/**
 * Class CssFileAggregator
 */
class CssFileAggregator extends FileAggregator
{
	/**
	 * The files to aggregate.
	 *
	 * @var array
	 */
	protected $arrFiles = array();


	/**
	 * The absolutize page.
	 *
	 * @var Database_Result
	 */
	protected $objAbsolutizePage = null;


	/**
	 * The aggregated file.
	 *
	 * @var string
	 */
	protected $strAggregatedFile = null;


	/**
	 * Create a new css file object.
	 *
	 * @param string $strScope
	 */
	public function __construct($strScope)
	{
		parent::__construct($strScope);

		// import the Theme+ master class
		$this->import('ThemePlus');
	}


	/**
	 * Add a file.
	 *
	 * @param LocalCssFile $objFile
	 *
	 * @return void
	 */
	public function add(LocalCssFile $objFile)
	{
		$this->arrFiles[] = $objFile;
	}


	/**
	 * @see LocalThemePlusFile::getFile
	 * @throws Exception
	 * @return string
	 */
	public function getFile()
	{
		if ($this->strAggregatedFile == null) {
			$arrFiles = array();
			$strKey   = count($this->arrFiles);
			foreach ($this->arrFiles as $objThemePlusFile)
			{
				if ($objThemePlusFile instanceof LocalCssFile) {
					if ($objThemePlusFile->isAggregateable()) {
						$strFile    = $objThemePlusFile->getFile();
						$objFile    = new File($strFile);
						$arrFiles[] = $strFile;
						$strKey .= sprintf(':%s-%d', md5($strFile), $objFile->mtime);
						continue;
					}
				}
				throw new Exception('Could not aggreagate the file: ' . $objFile);
			}

			$strTemp = 'system/scripts/stylesheet-' . substr(md5($strKey), 0, 8) . '.css';

			if (!file_exists(TL_ROOT . '/' . $strTemp)) {
				$this->import('Compression');

				// import the gzip compressor
				$strGzipCompressorClass = $this->Compression->getCompressorClass('gzip');
				$this->import($strGzipCompressorClass, 'Compressor');

				// import the url remapper
				$this->import('CssUrlRemapper');

				// build the content
				$strContent = '@charset "UTF-8";' . "\n";

				foreach ($arrFiles as $strFile)
				{
					$objFile = new File($strFile);

					// get the css code
					$strSubContent = $objFile->getContent();

					// detect and decompress gziped content
					$strSubContent = $this->ThemePlus->decompressGzip($strSubContent);

					// handle @charset
					$strSubContent = $this->ThemePlus->handleCharset($strSubContent);

					// remap url(..) entries
					$strSubContent = $this->CssUrlRemapper->remapCode($strSubContent, $strFile, $strTemp, $this->objAbsolutizePage ? true : false, $this->objAbsolutizePage);

					// trim content
					$strSubContent = trim($strSubContent);

					// append to content
					if (strlen($strSubContent) > 0) {
						$strContent .= $strSubContent . "\n";
					}
				}

				// write the file
				$objTemp = new File($strTemp);
				$objTemp->write($strContent);
				$objTemp->close();

				// create the gzip compressed version
				if ($GLOBALS['TL_CONFIG']['gzipScripts']) {
					$this->Compressor->compress($strTemp, $strTemp . '.gz');
				}
			}

			$this->strAggregatedFile = $strTemp;
		}
		return $this->strAggregatedFile;
	}


	/**
	 * @see ThemePlusFile::getEmbeddedHtml
	 * @return string
	 */
	public function getEmbeddedHtml($blnLazy = false)
	{
		// get the file
		$strFile = $this->getFile();
		$objFile = new File($strFile);

		// get the css code
		$strContent = $objFile->getContent();

		// handle @charset
		$strContent = $this->ThemePlus->handleCharset($strContent);

		// return html code
		return $this->getDebugComment() . '<style type="text/css">' . $strContent . '</style>';
	}


	/**
	 * @see ThemePlusFile::getIncludeHtml
	 * @return string
	 */
	public function getIncludeHtml($blnLazy = false)
	{
		// get the file
		$strFile = $this->getFile();

		// return html code
		return $this->getDebugComment() . '<link type="text/css" rel="stylesheet" href="' . TL_SCRIPT_URL . specialchars($strFile) . '" />';
	}
}