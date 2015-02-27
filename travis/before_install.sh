#!/bin/bash

mkdir tests/files

if [ $TRAVIS_PHP_VERSION = "hhvm" ]; then
   rm phpunit.xml
   mv phpunit.hhvm.xml phpunit.xml
fi
