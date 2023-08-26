![Logo](https://github.com/markocupic/markocupic/blob/main/logo.png)

# Composer File Copier Plugin

Let's assume you have developed a **composer package**. Now you want selected files inside your package to be copied to a defined location on your local file system after each `post-install-cmd` and `post-update-cmd` event. With a bit of configuration this **composer plugin** can do this job for you.

The configuration is made inside the [extra](https://getcomposer.org/doc/04-schema.md#extra) key of the `composer.json` file of your freshly programmed composer package.

## Installation

`composer require markocupic/composer-file-copier-plugin`

## Build your composer package

```
<project_root>/
├── app/
├── public/
└── vendor/
    └── code4nix/
        └── super-package/
            ├── .github/
            ├── src/
            ├── tests/
            ├── data/
            │   ├── foo.txt
            │   ├── style.css
            │   ├── config.yaml
            │   └── test1/
            │       ├── foo1.txt
            │       ├── style1.css
            │       ├── config1.yaml
            │       └── test2/
            │           ├── foo2.txt
            │           ├── style2.css
            │           └── config2.yaml
            └── composer.json    # configuration goes here!

```
<small>Big thanks to https://tree.nathanfriend.io for sharing this fancy tree generator. :heart:</small>
<!--
Edit the tree with this link:
https://tree.nathanfriend.io/?s=(%27optiHs!(%27fancy!true~fullPath!false~trailingSlash!true~rootDot!false)~F(%27F%27%3Cproject_root%3E3app3public3vendor3*code4nix36super-package5.github5src5Es5data5*fooA*8B*790*E156foo1A681B671966G06E25Gfoo2AG82BG729G0composer.jsH66%23%207uratiH%20goes%20here%27%3AC*%27)~versiH!%271%27)*%20%200C663%2FC*5%2F0G*7cHfig8style9.yamlA.txt0B.css0C%5CnEtestFsource!G6*Hon%01HGFECBA9876530*
-->

## Configuration

The configuration is made inside the **extra key** of the **composer.json** file inside your **composer package**.

> Note, that **source paths** are always relative to the installation directory of your package.

> Note, that **target paths** are always relative to the project root.

Inside the **composer.json** of your package:

```json
{
    "name": "code4nix/super-package",
    "type": "symfony-bundle",
    "require": {
        "php": "^8.1",
        "markocupic/composer-file-copier-plugin": "^0.1"
    },
    "autoload": {
        "psr-4": {
            "code4nix\\SuperPackage\\": "src/"
        }
    },
    "extra": {
        "composer-file-copier-plugin": [
            {
                "source": "data/foo.txt",
                "target": "foo.txt"
            },
            {
                "source": "data/foo.txt",
                "target": "foo.txt",
                "options": {
                    "OVERRIDE": true
                }
            },
            {
                "source": "data/test1",
                "target": "files/test1",
                "options": {
                    "OVERRIDE": true,
                    "DELETE": true
                }
            },
            {
                "source": "data",
                "target": "files/data",
                "options": {
                    "OVERRIDE": true
                },
                "filter": {
                    "NAME": [
                        "*.css",
                        "*.yaml"
                    ],
                    "DEPTH": [
                        "> 1",
                        "< 3"
                    ]
                }
            }
        ]
    }
}


```

| Mandatory keys | Description                                                                                                                                                                                |
|----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `source`       | Add a path to a file or directory. The path you set is relative to the package root.                                                                                                       |
| `target`       | Add a path to a file or directory. If the source path points to a file, then the destination path should point to a file as well. The target path is always relative to the document root. |

### Options

| Option    | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | Affects         |
|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------|
| `OVERRIDE` | Accepted values: boolean `true` or `false`. Overwrite existing newer files in target directory. Default to `false`.                                                                                                                                                                                                                                                                                                                                                                                                                             | files & folders |
| `DELETE`  | Accepted values: boolean `true` or `false`. Whether to delete files that are not in the source directory should be deleted. Default to `false`. This option is not available, when using filters.                                                                                                                                                                                                                                                                                                                                               | folders         |
| `MERGE`   | Merges source file with target file if it already exists. Accepted values: string <br/>- `replace`: Similar to PHP array merge, will replace any existing keys in target (Only formats supported `.json`). <br/>- `preserve`: Similar to PHP array merge, will preserve any existing keys in target (Only formats supported `.json`). <br/>- `none`: Does not merge. <br/>Defaults to `none` . | files           |

### Filters

| Filter     | Description                                                                                                                                              |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `NAME`     | Accepted values: array `"NAME": ["*.less","*.json"]`. See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#file-name) component.  |
| `NOT_NAME` | Accepted values: array `"NOT_NAME": ["*.php","*.js"]`. See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#file-name) component. |
| `DEPTH`    | Accepted values: array `"DEPTH": ["< 1","> 4"]` See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#directory-depth) component.  |

## Additional configuration
By default this package will not process the following package types: `library','metapackage','composer-plugin','project'`.
This can be overridden in your composer.json by specifying which package to exclude:
```json
{
    "extra": {
        "composer-file-copier-excluded": ["library", "metapackage", "composer-plugin"]
    }
}
```

## :warning: Last but not least!
> Note, that this is a very **powerful but also dangerous tool** that can **OVERRIDE/DELETE files/folders** and **DESTROY/DAMAGE your installation** if wrongly applied.
