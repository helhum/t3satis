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

	public function fetchRepositoryConfiguration() {
		return array(
			array(
				'type' => 't3git',
				'url' => 'git://git.typo3.org/TYPO3CMS/Extensions/news.git',
				'config' => array(
					'packageNameMapping' => array(
						'self' => 'typo3-git/news'
					)
				)
			),
			array(
				'type' => 't3git',
				'url' => 'git://git.typo3.org/TYPO3CMS/Extensions/extension_builder.git',
				'config' => array(
					'packageNameMapping' => array(
						'self' => 'typo3-git/extension_builder'
					)
				)
			),
			array(
				'type' => 't3git',
				'url' => 'git://git.typo3.org/TYPO3CMS/Extensions/gridelements.git',
				'config' => array(
					'packageNameMapping' => array(
						'self' => 'typo3-git/gridelements'
					)
				)
			),
		);
	}

} 