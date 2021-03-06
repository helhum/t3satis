<?php
namespace Helhum\T3Satis\Composer;

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
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository;
use Helhum\T3Satis\Composer\Repository\RepositoryCollectionInterface;

/**
 * Class Factory
 */
class Factory extends \Composer\Factory {

	protected $repoCollectionClasses = array(
		't3orgit' => 'Helhum\\T3Satis\\Composer\\Repository\\Typo3OrgRepositoryCollection'
	);

	protected function createRepositoryManager(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null) {
		$rm = parent::createRepositoryManager($io, $config, $eventDispatcher);

		$rm->setRepositoryClass('t3git', 'Helhum\\T3Satis\\Composer\\Repository\\Typo3ExtensionRepository');

		$repoCollections = $config->get('repository-collections');

		if ($repoCollections && is_array($repoCollections)) {
			foreach ($repoCollections as $repoCollection) {
				if (isset($repoCollection['type'])) {
					if (!isset($this->repoCollectionClasses[$repoCollection['type']])) {
						throw new \UnexpectedValueException('The collection type ' . $repoCollection['type'] . ' is unkown!', 1439304140);
					}
					$collectionClass = $this->repoCollectionClasses[$repoCollection['type']];
				} elseif (isset($repoCollection['className'])) {
					$collectionClass = $repoCollection['className'];
				} else {
					throw new \UnexpectedValueException('The collection must either be specified with "type" or "className"!', 1439304139);
				}

				if (!in_array('Helhum\\T3Satis\\Composer\\Repository\\RepositoryCollectionInterface', class_implements($collectionClass))) {
					throw new \UnexpectedValueException('The collection class must be autoloadable and must implement the RepositoryCollectionInterface', 1439304139);
				}
				/** @var RepositoryCollectionInterface $collection */
				$collection = new $collectionClass();
				$repos = $collection->fetchRepositoryConfiguration();
				$config->merge(array('repositories' => $repos));
			}
		}

		return $rm;
	}


} 