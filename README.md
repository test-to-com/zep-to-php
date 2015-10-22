ZEPHIR to PHP Translator
========================

This project implements a [ZEP](http://zephir-lang.com/) to [PHP](https://www.php.net/) translator.

The initial goal of this project, was to be able to create a PHP Version of [PHALCON](https://phalconphp.com), so that I could debug and discover, in depth, the workings of PHALCON.

This project, combined with the [PHALCON Debug Extension](https://github.com/test-to-com/phalcondbg) project has allowed me to finally Xdebug PHALCON.

Build Instructions
------------------

**DISCLAIMER:** I use Ubuntu 14.04 as my development OS. I have not tried building this on any other Linux Distribution or Windows.

### Linux/Unix/Mac

#### Requirements
You will need, working:

* [ZEPHIR](http://zephir-lang.com/) last version tested was 0.8.x

**NOTE:** You actually don't need ZEPHIR working, you need the _json-c_ library to be installed (the ZEPHIR Parser uses it to build it's intermediate files). You can use the install-json from the ZEPHIR Project to build this library.

This should basically work on any Linux Distribution:

```bash
./install
```

or if you want to run _tests_ agains the ZEP files included with ZEPHIR:

```bash
./install -t
```

** NOTE:** This adds a symlink _zeptophp_ to your user's bin directory _~/bin_, if it exists. Otherwise you can use _./bin/zeptophp_ to run the translator.

How to use
----------

If you have:

1. A _bin_ in the root of your user's home directory _~/bin_ , and
2. It's part of your _PATH_

then running the translator is as simple as 

```
zeptophp ...path to extension's zep files...
```

If not, then use the _zeptophp_ under the _bin_ directory from where you ran ./install.
