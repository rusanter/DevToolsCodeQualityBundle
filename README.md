DevToolsCodeQualityBundle
=========================

A simple and quick way to QA your Symfony application.

This Bundle is using the following tools to check your code:

 * [PHPLOC](http://github.com/sebastianbergmann/phploc)
 * [PHP_Depend](http://pdepend.org/)
 * [PHP Mess Detector](http://phpmd.org/)
 * [PHP Copy/Paste Detector](http://github.com/sebastianbergmann/phpcpd)
 * [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)

The result is presented as a set of html pages located inside your web directory.
Fire up http://localhost/qa/index.html file through a web server 
(this is important as the json/xml won't load into the page from file system).

This Bundle is not the replacement of something like 
[Jenkins for PHP](http://jenkins-php.org/) or [Sonar](http://www.sonarqube.org/).  
But it can help you to maintain quality of your code from the very beginning.

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your Symfony project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require --dev santer/code-quality-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.  
The bundle will be installed as a dev-dependency, so it will not affect your
production environment.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project. Please ensure you are
registering the bundle only for development environments.

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        // ...
        
        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            // ...
            $bundles[] = new DevTools\CodeQualityBundle\DevToolsCodeQualityBundle();
        }

        // ...
    }

    // ...
}
```

Step 3: Configure the Bundle
----------------------------

The two steps above are enough for most of common Symfony projects.  
You can jump to "Step 4" to start using the Bundle.

To change some Bundle behavior, add configuration to app/config/config_dev.yml.
Here are the full configuration options available at the moment
with the default values:

```yaml
dev_tools_code_quality:
    # Path where your code base is located
    inspect_path: src
    # Path where reports will be generated
    output_path: web/qa
    # Composer bin-dir
    bin_path: bin
    # Array of commands to run
    # The following values are allowed: ['phploc', 'pdepend', 'phpmd', 'phpcpd', 'phpcs']
    features:
        - phploc
        - pdepend
        - phpmd
        - phpcpd
        - phpcs
    # Options for PHP_CodeSniffer
    phpcs:
        # A list of standards to be checked, selected from the available set.
        # PHPCS supports [PSR1, PSR2, Zend, PHPCS, PEAR, Squiz, MySource] by default.
        # The "Symfony2" standard installed as a dependency and available 
        # on the following path: vendor/escapestudios/symfony2-coding-standard/Symfony2.
        standard:
            - Symfony2
```

### Change configuration on the fly

```console
$ php app/console dev:code-quality --help
Usage:
  dev:code-quality [options]

Options:
      --inspect-path=INSPECT-PATH  Path where your code base is located
      --output-path=OUTPUT-PATH    Path where reports will be generated
      --bin-path=BIN-PATH          Composer bin-dir
      --skip-phploc                Disable PHPLOC
      --skip-pdepend               Disable PHP_Depend
      --skip-phpmd                 Disable PHP Mess Detector
      --skip-phpcpd                Disable PHP Copy/Paste Detector
      --skip-phpcs                 Disable PHP_CodeSniffer
  -e, --env=ENV                    The Environment name. [default: "dev"]
```

Step 4: Improve your code by using this Bundle
----------------------------------------------

Use the command to generate reports:

```console
php app/console dev:code-quality
```

When the command finishes, the full report will be available at this page:
http://localhost/qa/index.html. Pease replace "localhost" with your site name.

Start from PHP_CodeSniffer page and look what you can improve in your code. 

Limitations
===========

 * There's nothing done to the standard reports - so expect XML, JSON and a pretty basic HTML files.
 * The html files in the web folder is just a quick visualizations of the json/xml files, don't expect much more... but if you want to make it prettier be my guest via pull-request.
 * [PHP Dead Code Detector](http://github.com/sebastianbergmann/phpdcd) wasn't included as it gives nothing but false positives in the Symfony apps I tested it with.
 * It won't auto generate documentation - like [PHPDox](http://phpdox.de/) or [phpDocumentor](http://www.phpdoc.org/)

TODO List
=========

The following features are planned in 1.0 release:

 * Use Symfony2 Coding Standards
 * Add --quiet option
 * Include lint:twig and lint:yaml commands to the report
 * Make report html files more user-friendly
 * Add more configuration options
