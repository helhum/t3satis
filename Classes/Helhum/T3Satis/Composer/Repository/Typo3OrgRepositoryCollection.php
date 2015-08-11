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
class Typo3OrgRepositoryCollection implements RepositoryCollectionInterface {

	const TYPO3_ORG_REPO_PATTERN = 'http://git.typo3.org/%s.git';

	/**
	 * Adds all git.typo3.org extension git repos
	 *
	 * @return array
	 */
	public function fetchRepositoryConfiguration() {
		$client = new \GuzzleHttp\Client();
		$res = $client->get('https://review.typo3.org/projects/?m=TYPO3CMS%2FExtensions%2F');

		$repos = json_decode(str_replace(")]}'\n", '', (string)$res->getBody()), TRUE);

		if (!is_array($repos)) {
			throw new \RuntimeException('Could not fetch typo3.org repos', 1439301580);
		}

		$allRepos = array();
		foreach (array_keys($repos) as $repo) {
			$repoUrl = sprintf(self::TYPO3_ORG_REPO_PATTERN, $repo);
			$allRepos[] = array(
				'type' => 't3git',
				'url' => $repoUrl,
				'config' => array(
					'packageNameMapping' => array(
						'self' => $this->getPackageKeyFromRepoUrl($repoUrl)
					)
				)
			);
		}

		//TODO: validate the repos first (and cache the validation result)
		return $allRepos;
	}

	/**
	 * @param string $repoUrl
	 * @return string
	 */
	protected function getPackageKeyFromRepoUrl($repoUrl) {
		$parts = explode('/', $repoUrl);
		return 'typo3-ter/' . str_replace(array('.git', '_'), array('', '-'), self::camelCaseToLowerCaseUnderscored(array_pop($parts)));
	}


	/**
	 * Returns a given CamelCasedString as an lowercase string with underscores.
	 * Example: Converts BlogExample to blog_example, and minimalValue to minimal_value
	 *
	 * @param string $string String to be converted to lowercase underscore
	 * @return string lowercase_and_underscored_string
	 */
	static public function camelCaseToLowerCaseUnderscored($string) {
		return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string));
	}

} 
