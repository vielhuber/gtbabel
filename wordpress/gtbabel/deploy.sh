#!/bin/bash

# copy venedors folder (this does not work!)
rm -rf vendor/
cp -r ./../../vendor ./vendor/

# use php-scoper(!)

# zip
zip -r ../gtbabel.zip .

# increase version number
# TODO

# add to subversion (or use github actions?)
# TODO