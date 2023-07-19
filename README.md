![Logo](https://github.com/markocupic/markocupic/blob/main/logo.png)

# Composer File Copier Plugin

Let's assume you have developed a **composer package** and you want files from the package to be copied to a defined location in the local file system after each `composer update` and `composer install`, then this **composer plugin** can do this work for you.

The configuration is made with the extra key of the composer.json file of the respective composer package.

> Note that this is a very **powerful but also dangerous tool** that can **delete directories** and **destroy your installation** if you do not care.

### Your package
```
<project_root>/
└── vendor/
    └── code4nix/
        └── super-package/
            ├── src/
            ├── data/
            │   ├── foo.txt # File to be copied after composer install/update
            │   ├── test1/
            │   │   └── foo1.txt
            │   └── test2/
            │       └── foo2.txt
            ├── ...
            └── composer.json # Configuration is made here
```
<small>Big thanks to https://tree.nathanfriend.io/ for sharing this fancy tree generator. :heart:</small>


### Configuration

The configuration is made inside the **extra key** of the **composer.json** file inside your **composer package**.

> Note! The **source path** are relative to the installation directory of your package.

> Note! The **target path** are relative to the project root directory of your package.

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
                "options": "OVERRIDE"
            },
            {
                "source": "data/test1",
                "target": "files/test1",
                "options": "OVERRIDE"
            },
            {
                "source": "data/test2",
                "target": "files/test2",
                "options": "OVERRIDE,DELETE"
            }
        ]
    }
}


```
| source & target                 | explain                                                                                                         | source (inside package)                                       | target (local file system)    |
|---------------------------------|-----------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------|-------------------------------|
| `data/foo.txt -> files/foo.txt` | Copy file from source to target. Source and target must contain a valid file path.                              | `<project_root>/vendor/code4nix/super-package/data/foo.txt`   | `<project_dir>/files/foo.txt` |
| `data/test1 -> files/test1`     | Copy the content of the source folder to the target folder. Source and target must contain a valid folder path. | `<project_root>/vendor/code4nix/super-package/data/test1/*.*` | `<project_dir>/files/test1`   |


#### Options

Use the `OVERRIDE` flag to define whether newer files in the destination folder should be overwritten. Use the `DELETE` flag if target files, that are not available in the source directory, should be deleted.

| Flag     | Description                                                  | Affects         |
|----------|--------------------------------------------------------------|-----------------|
| OVERRIDE | overwrite existing newer files in target directory           | files & folders |
| DELETE   | Whether to delete files that are not in the source directory | folders         |
