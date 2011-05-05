<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

#copyright


/**
 * Class EditorStyles
 * 
 * 
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Layout Additional Sources
 */
class EditorStyles extends LayoutAdditionalSources {
	public function __construct() {
		parent::__construct();
		$this->import('Input');
	}
	
	/**
	 * Get the editor content css files as string list.
	 * 
	 * @param string $strEditor
	 */
	public static function getEditorContentCSS($strEditor)
	{
		return implode(',', self::getEditorContentCSSArray($strEditor));
	}

	/**
	 * Get the editor content css files as array.
	 * 
	 * @param string $strEditor
	 */
	public static function getEditorContentCSSArray($strEditor)
	{
		$objEditorStyles = new EditorStyles();
		return $objEditorStyles->_getEditorContentCSSArray($strEditor);
	}
	
	/**
	 * Get the editor content css files as array.
	 * 
	 * @param string $strEditor
	 */
	protected function _getEditorContentCSSArray($strEditor)
	{
		$objPage = false;
		$intLayout = 0;
		
		switch ($this->Input->get('do'))
		{
			/* Article mode */
			case 'article':
				switch ($this->Input->get('table'))
				{
					/* Article editing */
					case '':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_article a
										ON
											p.id=a.pid
										WHERE
											a.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
						}
						break;
					
					/* Content element editing */
					case 'tl_content':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_article a
										ON
											p.id=a.pid
										INNER JOIN
											tl_content c
										ON
											a.id=c.pid
										WHERE
											c.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
						}
						break;
				}
				break;
			
			/* News mode */
			case 'news':
				switch ($this->Input->get('table'))
				{
					/* News editing */
					case 'tl_news':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_news_archive a
										ON
											p.id=a.jumpTo
										INNER JOIN
											tl_news n
										ON
											a.id=n.pid
										WHERE
											n.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
								break;
						}
						break;
				}
				break;
			
			/* Calendar mode */
			case 'calendar':
				switch ($this->Input->get('table'))
				{
					/* Calendar event editing */
					case 'tl_calendar_events':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_calendar c
										ON
											p.id=c.jumpTo
										INNER JOIN
											tl_calendar_events e
										ON
											c.id=e.pid
										WHERE
											e.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
								break;
						}
						break;
				}
				break;
			
			/* Form generator mode */
			case 'form':
				switch ($this->Input->get('table'))
				{
					/* Form field editing */
					case 'tl_form_field':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_form f
										ON
											p.id=f.jumpTo
										INNER JOIN
											tl_form_field e
										ON
											f.id=e.pid
										WHERE
											e.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
						}
						break;
				}
				break;
			
			/* Newsletter mode */
			case 'newsletter':
				switch ($this->Input->get('table'))
				{
					/* Newsletter editing */
					case 'tl_newsletter':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$objPage = $this->Database->prepare("
										SELECT
											p.*
										FROM
											tl_page p
										INNER JOIN
											tl_newsletter_channel c
										ON
											p.id=c.jumpTo
										INNER JOIN
											tl_newsletter n
										ON
											c.id=n.pid
										WHERE
											n.id=?")
									->execute($this->Input->get('id'));
								if ($objPage->next())
								{
									$objPage = $this->getPageDetails($objPage->id);
									$intLayout = $objPage->layout;
								}
								break;
						}
						break;
				}
				break;
			
			/* Theme mode */
			case 'themes':
				switch ($this->Input->get('table'))
				{
					/* Module editing */
					case 'tl_module':
						switch ($this->Input->get('act'))
						{
							case 'edit':
								$intLayout = $this->Input->get('id');
								break;
						}
						break;
				}
				break;
		}
		
		if (!$intLayout)
		{
			if (isset($GLOBALS['TL_HOOKS']['getEditorStylesLayout']) && is_array($GLOBALS['TL_HOOKS']['getEditorStylesLayout']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getEditorStylesLayout'] as $callback)
				{
					$this->import($callback[0]);
					$intResult = $this->$callback[0]->$callback[1]($strEditor);
					if ($intResult > 0)
					{
						$intLayout = intval($intResult);
						break;
					}
				}
			}
		}
		
		if (!$intLayout)
		{
			$objLayout = $this->Database->execute("
					SELECT
						*
					FROM
						tl_layout
					WHERE
						fallback='1'");
			if (!$objLayout->next())
			{
				return array();
			}
		}
		else
		{
			$objLayout = $this->Database->prepare("
					SELECT
						*
					FROM
						tl_layout
					WHERE
						id=?")
				->execute($intLayout);
			if (!$objLayout->next())
			{
				return array();
			}
		}
		
		$arrLayoutAdditionalSources = array_merge
		(
			array('0'),
			deserialize($objLayout->additional_source, true),
			$objPage ? $this->inheritAdditionalSources($objPage) : array()
		);
		
		$objAdditionalSources = $this->Database->prepare("
				SELECT
					*
				FROM
					tl_additional_source
				WHERE
						pid=?
					AND	(	id IN (" . implode(',', array_map('intval', $arrLayoutAdditionalSources)) . ")
						OR	force_editor_integration='1')
					AND (	type = 'css_url'
						OR  type = 'css_file')
				ORDER BY
					sorting")
			->execute($objLayout->pid);
		$arrIds = array();
		while ($objAdditionalSources->next())
		{
			if (	$objAdditionalSources->force_editor_integration
				||	in_array($strEditor, deserialize($objAdditionalSources->editor_integration, true)))
			{
				$arrIds[] = $objAdditionalSources->id;
			}
		}
		
		$arrSources = array();
		if (count($arrIds) > 0)
		{
			$arrArrAdditionalSources = $this->getSources($arrIds);
			foreach ($arrArrAdditionalSources as $strType => $arrAdditionalSources)
			{
				foreach ($arrAdditionalSources as $arrAdditionalSource)
				{
					$arrSources[] = $arrAdditionalSource['src'];
				}
			}
		}
		return $arrSources;
	}
	
	
	/**
	 * Overwrite be login status detection!
	 * 
	 * (non-PHPdoc)
	 * @see LayoutAdditionalSources::getBELoginStatus()
	 */
	protected function getBELoginStatus()
	{
		return false;
	}
}

?>