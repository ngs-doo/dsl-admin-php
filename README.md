## Introduction

Administrative web interface for data based on the DSL model

## Installation

Add the following to your composer.json and run `composer install`

    {
        "minimum-stability": "dev",
        "require": {
            "dsl-platform/admin": "dev-master"
        },
        "scripts": {
            "post-install-cmd": "DslPlatform\\Installer::install"
        },
        "autoload": {
            "psr-0": {
                "": ["src/", "Generated-PHP/", "Generated-PHP-UI/"]
            }
        }
    }

Afterwards, run `./php-compile.sh` to generate PHP sources. 