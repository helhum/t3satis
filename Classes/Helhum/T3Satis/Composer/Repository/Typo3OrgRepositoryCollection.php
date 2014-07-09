<?php
namespace Helhum\T3Satis\Composer\Repository;

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
 * Class Typo3OrgRepositoryCollection
 */
class Typo3OrgRepositoryCollection {

	const REPO_URL = 'https://git.typo3.org';
	const GIT_REPO_PREFIX = 'git://git.typo3.org/';
	const EXTENSION_PREFIX = 'TYPO3CMS/Extensions/';

	public function fetchRepositoryConfiguration() {

		$pageContent = file_get_contents(self::REPO_URL);

		$DOM = new \DOMDocument();
		$resultString = mb_convert_encoding($pageContent, 'html-entities', 'utf-8');
		$DOM->loadHTML($resultString);
		$repoEntries = array();

		$items = $DOM->getElementsByTagName('a');
		for ($i = 0; $i < $items->length; $i++) {
			if (strpos($items->item($i)->nodeValue, self::EXTENSION_PREFIX) === 0) {
				$repoEntries[] = array(
					'type' => 't3git',
					'url' => self::GIT_REPO_PREFIX . $items->item($i)->nodeValue
				);
			}
		}

		return $repoEntries;
	}

} 