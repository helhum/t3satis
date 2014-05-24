<?php
namespace Helhum\T3Satis\Composer\Repository\Vcs;

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

use Composer\Repository\Vcs\GitDriver;

/**
 * Class Typo3GitDriver
 */
class Typo3GitDriver extends GitDriver {

	const PACKAGE_NAME_PREFIX = 'typo3-git/';

	const PACKAGE_TYPE = 'typo3-cms-extension';

	public function getComposerInformation($identifier) {
		$composerInformation = parent::getComposerInformation($identifier);
		if (!$composerInformation) {
			$resource = sprintf('%s:ext_emconf.php', escapeshellarg($identifier));
			$this->process->execute(sprintf('git show %s', $resource), $emconf, $this->repoDir);
			if (!trim($emconf)) {
				return;
			}
			$EM_CONF = array();
			$_EXTKEY = $this->getExtensionKey();
			eval('?>' . $emconf);

			$composerInformation = $this->getComposerInformationFromEmConf($EM_CONF[$this->getExtensionKey()]);
			if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
				$this->cache->write($identifier, json_encode($composerInformation));
			}

			$this->infoCache[$identifier] = $composerInformation;

		}

		return $composerInformation;
	}


	protected function getComposerInformationFromEmConf($emconf) {
		return array_merge(array(
			'name' =>  $this->getPackageName($this->getExtensionKey()),
			'description' => (string) $emconf['description'],
			'version' => (string) $emconf['version'],
			'type' => self::PACKAGE_TYPE,
//			'time' => date('Y-m-d H:i:s', (int) $version->lastuploaddate),
			'authors' => array(
				array(
					'name' => (string) $emconf['author'],
					'email' => (string) $emconf['author_email'],
					'company' => (string) $emconf['author_company'],
//					'username' => (string) $version->ownerusername,
				)
			)),
			$this->getPackageLinks($emconf['constraints']),
			array(
				'replace' => array(
					(string) $this->getExtensionKey() => (string) $emconf['version'],
					'typo3-ext/' . $this->getExtensionKey() => (string) $emconf['version'],
//					'typo3-ter/' . (string) $this->getPackageName($this->getExtensionKey()) => (string) $emconf['version'],
				),
//				'dist' => array(
//					'url' => 'http://typo3.org/extensions/repository/download/' . $extension['extensionkey'] . '/' . $version['version'] . '/t3x/',
//					'type' => 't3x',
//				),
			)
		);
	}

	/**
	 * @param array $allDependencies
	 * @return array
	 */
	protected function getPackageLinks($allDependencies) {
		$packageLinks = array();
		foreach ($allDependencies as $kind => $dependencies) {
			$linkType = '';
			switch ($kind) {
				case 'depends':
					$linkType = 'require';
					break;
				case 'conflicts':
					$linkType = 'conflict';
					break;
				case 'suggests':
					$linkType = 'suggest';
					break;
				default:
					continue;
					break;
			}

			foreach ($dependencies as $name => $version) {
				$requiredVersion = explode('-', $version);
				$minVersion = trim($requiredVersion[0]);
				$maxVersion = (isset($requiredVersion[1]) ? trim($requiredVersion[1]) : '');

				if ((
						(empty($minVersion) || $minVersion === '0.0.0' || $minVersion === '*')
						&& (empty($maxVersion) || $maxVersion === '0.0.0' || $maxVersion === '*')
					)
					|| !preg_match('/^([\d]+\.[\d]+\.[\d]+)*(\-)*([\d]+\.[\d]+\.[\d]+)*$/', $version)
				) {
					$versionConstraint = '*';
				} elseif ($maxVersion === '0.0.0' || empty($maxVersion)) {
					$versionConstraint = '>= ' . $minVersion;
				} elseif (empty($minVersion) || $minVersion === '0.0.0') {
					$versionConstraint = '<= ' . $maxVersion;
				} elseif ($minVersion === $maxVersion) {
					$versionConstraint = $minVersion;
				} else {
					$versionConstraint = '>= ' . $minVersion . ', <= ' . $maxVersion;
				}

				$packageLinks[$linkType][$this->getPackageName($name)] = $versionConstraint;

			}


		}
		return $packageLinks;
	}

	/**
	 * @param string $extensionKey
	 * @return string
	 */
	protected function getPackageName($extensionKey) {
		switch ($extensionKey) {
			case 'php':
				return 'php';
			case 'typo3':
				return 'typo3/cms';
			default:
				return isset($this->repoConfig['composerName']) ? $this->repoConfig['composerName'] : self::PACKAGE_NAME_PREFIX . str_replace('_', '-', $extensionKey);
		}
	}

	/**
	 * @return string
	 */
	protected function getExtensionKey() {
		return isset($this->repoConfig['extKey']) ? $this->repoConfig['extKey'] : str_replace('.git', '', basename($this->url));
	}
}