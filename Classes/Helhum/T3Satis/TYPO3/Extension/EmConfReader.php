<?php
namespace Helhum\T3Satis\TYPO3\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class EmConfReader
 */
class EmConfReader {

	const STRATEGY_EVAL = 'eval';
	const STRATEGY_JSON = 'json';


	protected $readingStrategy;

	public function __construct($readingStrategy = self::STRATEGY_EVAL) {
		$this->readingStrategy = $readingStrategy;
	}

	/**
	 * @param string $emConfContent
	 * @return array
	 */
	public function readFromString($emConfContent) {
		$extensionConfig = NULL;
		switch ($this->readingStrategy) {
			case self::STRATEGY_EVAL:
				$extensionConfig = $this->readStringWithEvalConversion($emConfContent);
				break;
			case self::STRATEGY_JSON:
				$extensionConfig = $this->readStringWithJsonConversion($emConfContent);
				break;
			default:
				// throw news Exception
		}

		if (!is_array($extensionConfig)) {
			// throw new ReadingException
		}
		return $extensionConfig;
	}


	protected function readStringWithEvalConversion($emConfContent) {
		$EM_CONF = array();
		$_EXTKEY = uniqid('FakeKey');
		eval('?>' . $emConfContent);
		return $EM_CONF[$_EXTKEY];
	}

	protected function readStringWithJsonConversion($emConfContent) {
		$startPoint = strpos($emConfContent, '$EM_CONF[$_EXTKEY] = ');
		if ($startPoint === FALSE) {
			return FALSE;
		}
		$startPoint += 21;

		$emConfContent = substr($emConfContent, $startPoint);

		$emConfContent = preg_replace('/\/\/.*/', '', $emConfContent);
		$emConfContent = preg_replace('/array\s*\(/i', '{', $emConfContent);
		$emConfContent = str_replace('=>', ':', $emConfContent);
		$emConfContent = str_replace(')', '}', $emConfContent);
		$emConfContent = str_replace('?>', '', $emConfContent);
		$emConfContent = str_replace('};', '}', $emConfContent);
		$emConfContent = preg_replace('/\'_md5_values_when_last_written\'[ ]*:[ ]*\'[^\']*\',/m', '', $emConfContent);
		$emConfContent = str_replace('\'', '"', $emConfContent);
		$emConfContent = preg_replace('/",([\s]*)}/m', '"\1}', $emConfContent);
		$oc = $emConfContent;
		$emConfContent = preg_replace('/},([\s]*)}/m', '}\1}', $emConfContent);
		while ($oc !== $emConfContent) {
			$oc = $emConfContent;
			$emConfContent = preg_replace('/},([\s]*)}/m', '}\1}', $emConfContent);
		}

		$found = preg_match_all('/"[^"]*"/', $emConfContent, $matches);
		if ($found) {
			$search = array();
			$replace = array();
			foreach ($matches[0] as $match) {
				if (strpos($match, chr(10)) !== FALSE) {
					$search[] = $match;
					$replace[] = str_replace(chr(13), ' ', str_replace(chr(10), ' ', $match));
				}
			}

			$emConfContent = str_replace($search, $replace, $emConfContent);
		}
		return @json_decode($emConfContent, true);
	}
}