#!/bin/sh
set -eu
: "${code_path:?code_path must be set}"
: "${drupal_root:?drupal_root must be set}"
cd "$drupal_root"
FILE=installed.txt
if [ -f "$FILE" ]; then
    echo "Drupal Site Already Installed"
else

    cp "${code_path}/docker/composer.json" ./
    cp "${code_path}/docker/composer.lock" ./
    cp -r "${code_path}/docker/web" ./
    cp "${code_path}/docker/web/.htaccess" ./web
    chmod 644 web/.htaccess
    composer config --no-plugins allow-plugins.cweagans/composer-patches true
    composer install --no-interaction --no-progress
    cp web/sites/default/default.settings.php web/sites/default/settings.php
    cat "${code_path}/docker/settings.php.patch" >> web/sites/default/settings.php
    postconf -e 'relayhost = [mailfwd.nih.gov]'
    echo "Fixing group issue preventing db update"
    cp "${code_path}/docker/GroupRole.php" ./web/modules/contrib/group/src/Entity
    mkdir -p config/sync
    chown -R apache:apache web
    touch installed.txt
fi
