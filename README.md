[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jon48/webtrees-mod-translationtool/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jon48/webtrees-mod-translationtool/?branch=master)
[![Code Climate](https://codeclimate.com/github/jon48/webtrees-mod-translationtool/badges/gpa.svg)](https://codeclimate.com/github/jon48/webtrees-mod-translationtool)
![Licence](https://img.shields.io/github/license/jon48/webtrees-mod-translationtool)

# MyArtJaub Translation Tool Module
Administration module for webtrees to report the status of translations in MyArtJaub modules.

## Contents

* [License](#license)
* [Introduction](#introduction)
* [System requirements](#system-requirements)
* [Installation / Upgrading](#installation--upgrading)
* [Issues / Security](#issues--security)
* [Contacts](#contacts)

### License

* **webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees**
* Copyright (C) 2016 to 2022 Jonathan Jaubart.
* Derived from **webtrees** - Copyright (C) 2010 to 2022  webtrees development team.
* Derived from PhpGedView - Copyright (C) 2002 to 2010  PGV Development Team.

This program is free software; you can redistribute it and/or modify it under the
terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See the [LICENSE](LICENSE.md) included with this software for more detailed licensing
information.


### Introduction

This module is a utility module to manage translations in MyArtJaub modules.
It looks for strings to be translated in the different MyArtJaub modules and library 
and identify both the translations missing in the current language, as well as the 
translations declared in the modules, but without any apparent usage.

Being a utility module, it has a rather raw logic and design, adapted to my worklow, 
without any pretension to be universal or to cover all needs.

*Jonathan Jaubart*

### System requirements

It is required to run at least PHP 7.2 to be able to run the **webtrees-mod-translationtool** module.

The module attempts to identify alls paths that would contain translation strings.
To do so, it is looking in the `resources` folder of each module, but as well tries to load the paths 
used in the Composer class autoloader. To benefit the latter feature, the modules need to have been installed
through Composer, and not just copied into the `modules_v4` folder.

### Installation / Upgrading

It is recommended to install and upgrade **webtrees-mod-translationtool** via Composer.

To install the module, run the command:

```shell
composer require jon48/webtrees-mod-translationtool --ignore-platform-reqs
```
(the `--ignore-platform-reqs` is necessary if the PHP platform defined in the `composer.json` is below PHP 7.2)
	
In order to update the package, run the command:

```shell
composer update jon48/webtrees-mod-translationtool --ignore-platform-reqs
```

### Issues / Security

Issues should be raised in the [GitHub repository](https://github.com/jon48/webtrees-mod-translationtool/issues) for **jon48/webtrees-mod-translation**.

A [security policy document](SECURITY.md) has been issued for this repository.

### Contacts

General questions on the standard **webtrees** software should be addressed to the
[official forum](http://www.webtrees.net/index.php/forum)

You can contact the author (Jonathan Jaubart) of the **webtrees-mod-translationtool** projects 
through his personal [GeneaJaubart website](http://genea.jaubart.com/wt/) (link at the bottom of the page), 
or raise in issue in Github.

