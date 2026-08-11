[![Latest Version on Packagist](https://img.shields.io/packagist/v/cobbyio/cobby-connector-maho.svg?style=flat-square)](https://packagist.org/packages/cobbyio/cobby-connector-maho)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue.svg)](http://www.php.net)

# cobby for Maho

[cobby](http://www.cobby.io) is a PIM system that loves ❤️ Excel.
Designed to help Maho users manage their online shop catalog faster by connecting all product data in real-time with Excel without any import/export.
This extension for [Maho](https://mahocommerce.com) makes your catalog management much more efficient and faster without any additional knowledge required as all product updates will be made in Excel and directly updated in Maho.

Maho is the composer-based continuation of Magento 1 / OpenMage, rebuilt on PHP 8.3+. This repository is the Maho port of the cobby connector; for Magento 1 / OpenMage use [cobby-connector-magento](https://github.com/cobbyio/cobby-connector-magento), and for Magento 2.x use [cobby-connector-magento2](https://github.com/cobbyio/cobby-connector-magento2).

# Compatibility

- [Maho](https://mahocommerce.com)
- PHP 8.3+

# Installation

Install via Composer into your Maho project. The maho-composer-plugin deploys the module into the `community` code pool via modman symlinks automatically:

```bash
composer require cobbyio/cobby-connector-maho
./maho migrate
./maho cache:flush
```

Alternatively, download the latest ZIP from the master branch of this repo either by cloning the repo or by clicking on the [ZIP](https://github.com/cobbyio/cobby-connector-maho/archive/refs/heads/master.zip) file within GitHub.

# Configuration

In order to use cobby with Maho, a cobby account has to be created beforehand. With the account, it’s possible to configure the Maho extension to work properly.

If you don't have a cobby account:

1. Sign up for a free trial at [www.cobby.io](http://www.cobby.io)
2. After you have signed up to cobby, follow the steps of the configuration wizard

# Support

If you have any issues with this extension, open an issue on [GitHub](https://github.com/cobbyio/cobby-connector-maho/issues).
Alternatively feel free to contact us via email at support@cobby.io or via our website [www.cobby.io](http://www.cobby.io).

# Contribution

Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://github.com/cobbyio/cobby-connector-maho/pulls)

# Change log

See [Changelog](CHANGELOG.md)

# License

See [License](LICENSE.md)
