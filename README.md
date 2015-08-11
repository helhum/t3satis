# T3-Satis
Repository Generator with support for TYPO3 CMS extension (git) repositories

## Example satis.json

```
{
    "name": "vendor's",
    "homepage": "http://composer.vendor.de",
    "repositories": [
        {
            "type": "t3git",
            "url": "git@bitbucket.org:vendor/project_site.git",
            "config": {
                "extensionKeyMapping": {
                    "self": "project_site"
                },
                "packageNameMapping": {
                    "self": "helhum/project-site"
                }
            }
        }
    ],
    "config": {
        "extensionKeyMapping": {
            "git@bitbucket.org:vendor/project_site.git": "project_site"
        },
        "packageNameMapping": {
            "project_site": "helhum/project-site"
        },
        "repository-collections": [
            {"className": "Helhum\\T3Satis\\Composer\\Repository\\Typo3OrgRepositoryCollection"}
        ]
    },
    "require-all": true
}

```
