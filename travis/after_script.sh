#!/bin/bash

if [ $TRAVIS_PHP_VERSION != "hhvm" ]; then
  wget https://scrutinizer-ci.com/ocular.phar
  php ocular.phar code-coverage:upload --format=php-clover coverage.xml
fi
