<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Theme+
 * Copyright (C) 2010,2011 InfinitySoft <http://www.infinitysoft.de>
 *
 * Extension for:
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  2010,2011 InfinitySoft <http://www.infinitysoft.de>
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Theme+
 * @license    LGPL
 */


/**
 * Class FileAggregator
 */
abstract class FileAggregator extends LocalThemePlusFile
{
	/**
	 * The scope of this aggregator
	 *
	 * @var string
	 */
	protected $strScope;


	/**
	 * Create a new aggregator
	 *
	 * @param string $strScope
	 */
	public function __construct($strScope)
	{
		$this->strScope = $strScope;
	}

	
	/**
	 * Get the scope of this aggregator
	 *
	 * @return string
	 */
	public function getScope()
	{
		return $this->strScope;
	}


	/**
	 * @see ThemePlusFile::getDebugComment
	 * @return string
	 */
	protected function getDebugComment()
	{
		$this->import('ThemePlus');
		if ($GLOBALS['TL_CONFIG']['debugMode'] || $this->ThemePlus->getBELoginStatus())
		{
			return "<!--\nfile aggregation:\n - " . implode("\n - ", array_map(create_function('$objFile', 'return $objFile->getFile();'), $this->arrFiles)) . ', scope: ' . $this->getScope() . "\n-->\n";
		}
		return '';
	}
}