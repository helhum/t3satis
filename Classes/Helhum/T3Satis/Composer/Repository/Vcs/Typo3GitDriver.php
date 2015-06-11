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
use Helhum\T3Satis\TYPO3\Extension\EmConfReader;

/**
 * Class Typo3GitDriver
 */
class Typo3GitDriver extends GitDriver {

	const PACKAGE_NAME_PREFIX = 'typo3-ter/';

	const PACKAGE_TYPE = 'typo3-cms-extension';

	protected $extensionKeyMapping = array();

	protected $packageNameMapping = array();


	public function getComposerInformation($identifier) {
		$this->buildNameMapping();
		$composerInformation = parent::getComposerInformation($identifier);

		if (!$composerInformation) {
			$resource = sprintf('%s:ext_emconf.php', escapeshellarg($identifier));
			$this->process->execute(sprintf('git show %s', $resource), $emConf, $this->repoDir);
			if (!trim($emConf)) {
				// No emconf at all, we skip
				return;
			}
			$emConfConverter = new EmConfReader();
			$extensionConfig = $emConfConverter->readFromString($emConf);
			if (empty($extensionConfig) || !preg_match('/^[\d]+\.[\d]+(\.[\d])*$/', str_replace('-dev', '', $extensionConfig['version']))) {
				// Could not read extension config or invalid version number, we skip
				return;
			}

			$composerInformation = $this->getComposerInformationFromEmConf($extensionConfig, $identifier);

			if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
				$this->cache->write($identifier, json_encode($composerInformation));
			}

			$this->infoCache[$identifier] = $composerInformation;
		}

		return $composerInformation;
	}

	protected function buildNameMapping() {
		$extensionKeyMapping = $this->config->get('extensionKeyMapping');
		if (is_array($extensionKeyMapping)) {
			$this->extensionKeyMapping = $extensionKeyMapping;
			unset($extensionKeyMapping);
		}
		if (isset($this->repoConfig['config']['extensionKeyMapping']) && is_array($this->repoConfig['config']['extensionKeyMapping'])) {
			foreach ($this->repoConfig['config']['extensionKeyMapping'] as $url => $extensionKey) {
				if ($url === 'self') {
					$url = $this->url;
				}
				$this->extensionKeyMapping[$url] = $extensionKey;
			}
		}
		$packageNameMapping = $this->config->get('packageNameMapping');
		if (is_array($packageNameMapping)) {
			$this->packageNameMapping = $packageNameMapping;
			unset($packageNameMapping);
		}
		if (isset($this->repoConfig['config']['packageNameMapping']) && is_array($this->repoConfig['config']['packageNameMapping'])) {
			foreach ($this->repoConfig['config']['packageNameMapping'] as $extensionKey => $packageName) {
				if ($extensionKey === 'self') {
					$extensionKey = $this->getExtensionKey();
				}
				$this->packageNameMapping[$extensionKey] = $packageName;
			}
		}
	}

	protected function getComposerInformationFromEmConf($emConf, $identifier) {
		$basicInfo = array(
			'name' =>  $this->getPackageName($this->getExtensionKey()),
			'description' => (string) $emConf['description'],
			'version' => (string) $emConf['version'],
			'type' => self::PACKAGE_TYPE,
			'authors' => array(
				array(
					'name' => isset($emConf['author']) ? $emConf['author'] : '',
					'email' => isset($emConf['author_email']) ? $emConf['author_email'] : '',
					'company' => isset($emConf['author_company']) ? $emConf['author_company'] : '',
				)
			),
			'autoload' => array(
				'classmap' => array('')
			)
		);
		$replaceInfo = array(
				'replace' => array(
					(string) $this->getExtensionKey() => (string) $emConf['version'],
					'typo3-ext/' . $this->getExtensionKey() => (string) $emConf['version'],
				),
			);

		if (strpos($basicInfo['name'], self::PACKAGE_NAME_PREFIX) !== 0) {
			$replaceInfo['replace'][self::PACKAGE_NAME_PREFIX . str_replace('_', '-', $this->getExtensionKey())] = (string)$emConf['version'];
		}

		$branchAlias = $this->buildBranchAlias($emConf, $identifier);
		if ($branchAlias === FALSE) {
			$aliasInfo = array();
		} else {
			$aliasInfo = array(
				'extra' => array(
					'branch-alias' => $branchAlias
				)
			);
		}

		$additionalRootConfig = isset($this->repoConfig['config']['root-config']) ? $this->repoConfig['config']['root-config'] : array();

		return array_merge($basicInfo, $this->getPackageLinks($emConf['constraints']), $replaceInfo, $aliasInfo, $additionalRootConfig);
	}

	protected function buildBranchAlias($emConf, $identifier) {
		$branches = $this->getBranches();
		$tags = $this->getTags();

		$branchName = isset($branches[$identifier]) ? $identifier : array_search($identifier, $branches);
		$tagName = isset($tags[$identifier]) ? $identifier : array_search($identifier, $tags);

		if (!empty($tagName) || empty($branchName)) {
			return FALSE;
		}

		$version = str_replace('-dev', '', $emConf['version']);
		$versionParts = array_map('intval', explode('.', $version));
		$versionParts[2] = 'x';
		$devBranchVersion = implode('.', $versionParts) . '-dev';

		if ($devBranchVersion !== $branchName) {
			return array(
				'dev-' . $branchName => $devBranchVersion
			);
		}

		return FALSE;
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
		if (isset($this->packageNameMapping[$extensionKey])) {
			return $this->packageNameMapping[$extensionKey];
		}
		switch ($extensionKey) {
			case 'php':
				return 'php';
			case 'typo3':
				return 'typo3/cms';
			case 'about':
			case 'aboutmodules':
			case 'adodb':
			case 'backend':
			case 'belog':
			case 'beuser':
			case 'cms':
			case 'context_help':
			case 'core':
			case 'cshmanual':
			case 'css_styled_content':
			case 'dbal':
			case 'documentation':
			case 'extbase':
			case 'extentionmanager':
			case 'extra_page_cm_options':
			case 'feedit':
			case 'felogin':
			case 'filelist':
			case 'filemetadata':
			case 'fluid':
			case 'form':
			case 'func':
			case 'func_wizards':
			case 'impexp':
			case 'indexed_search':
			case 'indexed_search_mysql':
			case 'info':
			case 'info_pagetsconfig':
			case 'install':
			case 'lang':
			case 'linkvalidator':
			case 'lowlevel':
			case 'opendocs':
			case 'perm':
			case 'recordlist':
			case 'recycler':
			case 'reports':
			case 'rsaauth':
			case 'rtehtmlarea':
			case 'saltedpasswords':
			case 'scheduler':
			case 'setup':
			case 'sv':
			case 'sys_action':
			case 'sys_note':
			case 't3editor':
			case 't3skin':
			case 'taskcenter':
			case 'tstemplate':
			case 'version':
			case 'viewpage':
			case 'wizard_crpages':
			case 'wizard_sortpages':
			case 'workspaces':
				return 'typo3/cms-' . $extensionKey;
			default:
				return self::PACKAGE_NAME_PREFIX . str_replace('_', '-', $extensionKey);
		}
	}

	/**
	 * @return string
	 */
	protected function getExtensionKey() {
		return isset($this->extensionKeyMapping[$this->url]) ? $this->extensionKeyMapping[$this->url] : str_replace('.git', '', basename($this->url));
	}
}