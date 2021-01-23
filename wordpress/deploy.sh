#!/bin/bash

# switch to composer 1 (https://github.com/humbug/php-scoper/issues/452)
composer self-update --1

# output commands
set -x

# save current folder
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# determine next version
v=`git describe --abbrev=0 --tags 2>/dev/null`
n=(${v//./ })
n1=${n[0]}
n2=${n[1]}
n3=${n[2]}
if [ -z "$n1" ] && [ -z "$n2" ] && [ -z "$n3" ]; then n1=1; n2=0; n3=0;else n3=$((n3+1)); fi
if [ "$n3" == "10" ]; then n3=0; n2=$((n2+1)); fi
if [ "$n2" == "10" ]; then n2=0; n1=$((n1+1)); fi
v_new="$n1.$n2.$n3"

# delete symlink that has been created when developing locally
unlink vendor

# copy composer files to current folder (one level up) and run composer install
cp ../composer.json ./composer.json
cp -r ../src ./src
cp -r ../components ./components
cp ../helpers.php ./helpers.php
composer install --no-dev
composer update --no-dev

# increase version number in readme.txt and main php
sed -i -e "s/Stable tag: [0-9]\.[0-9]\.[0-9]/Stable tag: $v_new/" ./readme.txt
sed -i -e "s/ \* Version: [0-9]\.[0-9]\.[0-9]/ * Version: $v_new/" ./gtbabel.php

# do the prefixing with php-scoper
wget https://github.com/humbug/php-scoper/releases/download/0.13.1/php-scoper.phar
rm -f ./gtbabel.zip
rm -f ./close2translate.zip
php ./php-scoper.phar add-prefix --config scoper.inc.php
cd ./build
composer dump-autoload
cd $SCRIPT_DIR
sleep 3

# rename and cleanup the build directory
mv ./build/ ./gtbabel/
rm -f ./gtbabel/composer.json
rm -f ./gtbabel/composer.lock
rm -f ./gtbabel/php-scoper.phar
rm -f ./gtbabel/deploy.sh
rm -f ./gtbabel/scoper.inc.php
rm -rf ./gtbabel/locales/
rm -rf ./gtbabel/logs/

# make an official zip (plugin repo)
zip -r ./gtbabel.zip ./gtbabel

# make an official zip (manual deploy)
cp -r ./gtbabel ./close2translate/
cd ./close2translate/
sed -i -e "s/Plugin URI: https:\/\/github\.com\/vielhuber\/gtbabel/Plugin URI: https:\/\/close2\.de/g" ./gtbabel.php
sed -i -e "s/Author: David Vielhuber/Author: close2 new media GmbH/g" ./gtbabel.php
sed -i -e "s/Author URI: https:\/\/vielhuber\.de/Author URI: https:\/\/close2\.de/g" ./gtbabel.php
find . -type d -name "*" -print0 | xargs -0 rename 's/Gtbabel/close2translate/g' {}
find . -type d -name "*" -print0 | xargs -0 rename 's/gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 rename 's/Gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 rename 's/gtbabel/close2translate/g' {}
find . -type f -name "*" -print0 | xargs -0 sed -i -e 's/Gtbabel/close2translate/g'
find . -type f -name "*" -print0 | xargs -0 sed -i -e 's/gtbabel/close2translate/g'
cp ../composer.json ./composer.json
composer dump-autoload
rm -f ./composer.json
rm -f ./composer.lock
msgfmt ./languages/close2translate-plugin-de_DE.po -o ./languages/close2translate-plugin-de_DE.mo
cd ..
zip -r ./close2translate.zip ./close2translate
rm -rf ./close2translate/

# add to subversion
if [[ ( -z "$1" ) || ( $1 != "--no-deploy" ) ]]; then
mkdir svn
cd ./svn
svn co https://plugins.svn.wordpress.org/gtbabel . --quiet
sleep 2
svn cleanup --quiet
svn update --quiet
sleep 2
svn rm ./trunk/* --quiet
cp -r ./../gtbabel/. ./trunk/
svn add ./trunk/* --quiet
svn rm ./assets/* --quiet
cp -r ./../gtbabel/assets/plugin/. ./assets/
svn add ./assets/* --quiet
svn cp ./trunk ./tags/$v_new --quiet
svn ci -m "$v_new" --username vielhuber --quiet
cd $SCRIPT_DIR
fi

# remove obsolete files
rm -rf ./svn/
rm -rf ./gtbabel/
rm -f ./php-scoper.phar
rm -rf ./vendor/
rm -rf ./src
rm -rf ./components
rm -f ./helpers.php
rm -f ./composer.json
rm -f ./composer.lock

# reestablish symlink
ln -s ../vendor ./vendor

# git push + tag
cd ./../
git add -A . && git commit -m "$v_new" && git push origin HEAD && git tag -a $v_new -m "$v_new" && git push --tags

# switch back to composer 2
composer self-update --2
