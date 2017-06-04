# Configurable package installer

YAML based project installer inspired by [`IceHawk installer`](https://github.com/icehawk/installer) and [`Koriym.PhpSkeleton`](https://github.com/koriym/Koriym.PhpSkeleton)

[![Build Status](https://travis-ci.org/php-api-clients/installer.svg?branch=master)](https://travis-ci.org/php-api-clients/installer)
[![Latest Stable Version](https://poser.pugx.org/api-clients/installer/v/stable.png)](https://packagist.org/packages/api-clients/installer)
[![Total Downloads](https://poser.pugx.org/api-clients/installer/downloads.png)](https://packagist.org/packages/api-clients/installer)
[![Code Coverage](https://scrutinizer-ci.com/g/php-api-clients/installer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/php-api-clients/installer/?branch=master)
[![License](https://poser.pugx.org/api-clients/installer/license.png)](https://packagist.org/packages/api-clients/installer)
[![PHP 7 ready](http://php7ready.timesplinter.ch/php-api-clients/installer/badge.svg)](https://travis-ci.org/php-api-clients/installer)

## Usage

## require api-clients/installer

Add `api-clients/installer` to `require` of your `composer.json`.

## post-create-project-cmd

It is recommended to use the following `post-create-project-cmd` script. This is the bare minimum require for the installer to work. [`api-clients/skeleton` for example also runs resource generation, code style fixes, and QA checks after install.](https://github.com/php-api-clients/skeleton/blob/30a2d61453a014052ab84ba11687d3cd5fe23a4f/composer.json#L97-L104):

```json
{

    "scripts": {
        "post-create-project-cmd": [
            "ApiClients\\Tools\\Installer\\Installer::postCreateProject",
            "composer update --no-autoloader --ansi",
            "composer update --ansi"
        ]
    }
}

```

## installer.yml

Create an `installer.yml` in the project root of the package you want to create use the installer for. The following configuration is from `api-clients/skeleton`:

```yaml
package: api-clients/skeleton
text:
  welcome: "Welcome to the API Clients new client installer."
  ascii_art_file:
    - ascii.small.art
    - ascii.medium.art
    - ascii.large.art
  ascii_art_package: api-clients/branding
env:
  current_ns: "ApiClients\\Skeleton"
  current_ns_tests: "ApiClients\\Tests\\Skeleton"
  require:
    - installer
    - installer-client-operations
  scripts:
    - post-create-project-cmd
questions:
  author_name:
    description: "Author name"
    question: "What is your name?"
  author_email:
    description: "Author email"
    question: "What is your email address?"
    validate: "Assert\\Assertion::email"
  package_name:
    description: "Your package"
    question: "What is your package name?"
    default: "vendor-name/package-name"
  path_src:
    description: "Your project sources location"
    question: "What is your project sources location?"
    default: "src/"
  path_tests:
    description: "Your project tests location"
    question: "What is your project tests location?"
    default: "tests/"
  ns_vendor:
    description: "Your namespace"
    question: "What is your vendor namespace?"
    default: "MyVendor"
  ns_tests_vendor:
    description: "Your test namespace"
    question: "What is your vendor test namespace?"
    default: "MyVendor\\Tests"
  ns_project:
    description: "Your project namespace"
    question: "What is your project namespace?"
    default: "MyProject"
operations:
  - "ApiClients\\Tools\\Installer\\Operation\\ComposerJson::create"
  - "ApiClients\\Tools\\Installer\\Operation\\UpdateNamespaces::create"
```

Lets break that down and describe what each section does:

### package

The name of the skeleton package.

### text

Text options used to create a welcome at the start of the installer.

##### text.welcome

Welcome message.

##### text.ascii_art_file

Array with ASCII files. 

##### text.ascii_art_package

ASCII art package, if not provided the skeleton is searched for the ASCII art files.

### config

Array with additional configuration options.

### questions

The questions asked used for gathering the information needed to perform the operations.

### operations

Operations run after all the questions have been answered.

# License

The MIT License (MIT)

Copyright (c) 2017 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
