#!/bin/bash

# switch to composer 1 (https://github.com/humbug/php-scoper/issues/452)
composer self-update --1

# output commands
set -x

# save current folder
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# copy all files to subfolder
rsync -Rr . ./close2translate/

# go into that folder
cd close2translate/

# delete symlink that has been created when developing locally
unlink vendor

# copy some files to current folder (two levels up)
cp ../../composer.json ./composer.json
cp -r ../../src ./src
cp -r ../../components ./components
cp ../../helpers.php ./helpers.php

# make replacements
sed -i -e "s/Plugin URI: https:\/\/github\.com\/vielhuber\/gtbabel/Plugin URI: https:\/\/close2\.de/g" ./gtbabel.php
sed -i -e "s/Author: David Vielhuber/Author: close2 new media GmbH/g" ./gtbabel.php
sed -i -e "s/Author URI: https:\/\/vielhuber\.de/Author URI: https:\/\/close2\.de/g" ./gtbabel.php
find . -type d -name "*" -print0 | xargs -0 rename 's/Gtbabel/close2translate/g' {}
find . -type d -name "*" -print0 | xargs -0 rename 's/gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 rename 's/Gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 rename 's/gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 sed -i -e 's/Gtbabel/close2translate/g'
find . -type f -name "*" -print0 | xargs -0 sed -i -e 's/gtbabel/close2translate/g'
msgfmt ./languages/close2translate-plugin-de_DE.po -o ./languages/close2translate-plugin-de_DE.mo

# run composer install
composer install --no-dev
composer update --no-dev

# do the prefixing with php-scoper
wget https://github.com/humbug/php-scoper/releases/download/0.13.1/php-scoper.phar
php ./php-scoper.phar add-prefix --config scoper.inc.php
cd ./build/
composer dump-autoload
cd $SCRIPT_DIR
cd ./close2translate/
sleep 3

# rename and cleanup the build directory
mv ./build/ ./close2translate/
rm -f ./close2translate/close2translate.zip
rm -f ./close2translate/gtbabel.zip
rm -f ./close2translate/composer.json
rm -f ./close2translate/composer.lock
rm -f ./close2translate/php-scoper.phar
rm -f ./close2translate/deploy-plugin.sh
rm -f ./close2translate/deploy-zip.sh
rm -f ./close2translate/scoper.inc.php
rm -rf ./close2translate/locales/
rm -rf ./close2translate/logs/

# make a zip
zip -r ./../close2translate.zip ./close2translate

# remove obsolete folder
cd $SCRIPT_DIR
rm -rf close2translate/

# switch back to composer 2
composer self-update --2