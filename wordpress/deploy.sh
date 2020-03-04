#!/bin/bash
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# delete symlink that has been created when developing locally
unlink vendor

# copy composer files to current folder (one level up) and run composer install
cp ../composer.json ./composer.json
cp -r ../src ./src
cp ../helpers.php ./helpers.php
composer install --no-dev
composer update --no-dev

# do the prefixing with php-scoper
wget https://github.com/humbug/php-scoper/releases/download/0.13.1/php-scoper.phar
rm -f ./gtbabel.zip
php ./php-scoper.phar add-prefix --config scoper.inc.php
cd ./build
composer dump-autoload
cd $SCRIPT_DIR
sleep 3

# rename and zip the build directory
mv ./build/ ./gtbabel/
zip -r ./gtbabel.zip ./gtbabel -x "gtbabel/composer.lock" -x "gtbabel/php-scoper.phar" -x "gtbabel/deploy.sh" -x "gtbabel/composer.json" -x "gtbabel/scoper.inc.php" -x \*"gtbabel/locales/"\* -x \*"gtbabel/logs/"\*
rm -rf ./gtbabel

# increase version number
# TODO

# add to subversion (or use github actions?)
# TODO

# remove obsolete files
rm -f ./php-scoper.phar
rm -rf ./vendor/
rm -rf ./src
rm -f ./helpers.php
rm -f ./composer.json
rm -f ./composer.lock

# reestablish symlink
ln -s ../vendor ./vendor