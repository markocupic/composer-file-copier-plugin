![Logo](https://github.com/markocupic/markocupic/blob/main/logo.png)

# Composer File Copier Plugin

With this Composer plugin you can copy files from a package to a target directory during the `composer install` or `composer update` process.

The configuration is made in the extra key of the composer.json file of the respective package.

Note that this is a very **powerful but also dangerous tool** that can **delete directories** and **destroy installations** if you do not care.

## Configuration

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
    "contao": {
      "sources": {
        "data/foo.txt": "foo.txt",
        "data/foo.txt": "files/foo.txt|OVERRIDE",
        "data/test": "files/test|OVERRIDE",
        "data/test2": "files/test2/|OVERRIDE|DELETE"
      }
    }
  }
}

```
| Flag                             | What?                                                       | Source                                                     | Target                        |
|----------------------------------|-------------------------------------------------------------|------------------------------------------------------------|-------------------------------|
| `data/foo.txt": "files/foo.txt`  | Copy file from source to destination.                       | `<project_dir>/vendor/code4nix/super-package/data/foo.txt` | `<project_dir>/files/foo.txt` |
| `data/sub": "files/sub`          | Copy files & folders of source folder to the target folder. | `<project_dir>/vendor/code4nix/super-package/data/sub/*.*` | `<project_dir>/files/sub`     |


### Flags

Use the `OVERRIDE` and `DELETE` flags to define whether newer files in the destination folder should be overwritten files that are not in the source directory should be deleted.

| Flag     | Description                                                  | Affects         |
|----------|--------------------------------------------------------------|-----------------|
| OVERRIDE | overwrite existing newer files<br/> in target directory      | files & folders |
| DELETE   | Whether to delete files that are not in the source directory | folders         |
