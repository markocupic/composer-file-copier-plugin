![Logo](https://github.com/markocupic/markocupic/blob/main/logo.png)

# Composer File Copier Plugin

Let's assume you have developed a **composer package**, and you want files from the package to be copied to a defined location in the local file system after each `post-install-cmd` and `post-update-cmd`, then this **composer plugin** can do this work for you.

The configuration is made inside the extra key of the composer.json of the respective composer package.

> Note that this is a very **powerful but also dangerous tool** that can **delete directories** and **destroy your installation** if you do not care.

### Installation

`composer require markocupic/composer-file-copier-plugin`


### Build your composer package
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
<small>Big thanks to https://tree.nathanfriend.io/ for sharing this fancy tree generator. :heart:</small>


### Configuration

The configuration is made inside the **extra key** of the **composer.json** file inside your **composer package**.

> Note! The **source path** are relative to the installation directory of your package.

> Note! The **target path** are relative to the project root.

Inside the **composer.json** of your package:
```json
{
    "name": "code4nix/super-package",
    "type": "symfony-bundle",
    "require": {
        "php": "^8.1",
        "markocupic/composer-file-copier-plugin": "^1.0"
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


#### Options

| Option     | Description                                                                                                                                                                                       | Affects         |
|------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------|
| `OVERRIDE` | Accepted values: boolean `true` or `false`. Overwrite existing newer files in target directory. Default to `false`.                                                                               | files & folders |
| `DELETE`   | Accepted values: boolean `true` or `false`. Whether to delete files that are not in the source directory should be deleted. Default to `false`. This option is not available, when using filters. | folders         |


#### Filters

| Filter     | Description                                                                                                                                              |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `NAME`     | Accepted values: array `"NAME": ["*.less","*.json"]`. See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#file-name) component.  |
| `NOT_NAME` | Accepted values: array `"NOT_NAME": ["*.php","*.js"]`. See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#file-name) component. |
| `DEPTH`    | Accepted values: array `"DEPTH": ["< 1","> 4"]` See [Symfony Finder](https://symfony.com/doc/current/components/finder.html#directory-depth) component.  |
