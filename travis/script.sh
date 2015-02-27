#!/bin/bash

if [ $TRAVIS_PHP_VERSION != "hhvm" ]; then
  bin/phpunit --coverage-text --coverage-clover coverage.xml
else
  bin/phpunit
fi

bin/phpspec run
