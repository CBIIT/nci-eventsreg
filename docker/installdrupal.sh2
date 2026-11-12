#!/bin/sh
FILE=installed.txt
if [ -f "$FILE" ]; then
    echo "Drupal Site Already Installed"
else
    touch installed.txt

    cp $code_path/docker/composer.lock ./
    cp $code_path/docker/composer.lock ./
    cp -r $code_path/docker/web ./
    cp $code_path/docker/web/.htaccess ./web
    chmod 644 web/.htaccess
    composer config --no-plugins allow-plugins.cweagans/composer-patches true
    composer install
    cp web/sites/default/default.settings.php web/sites/default/settings.php
    cat $code_path/docker/settings.php.patch >> web/sites/default/settings.php
    echo "relayhost = [mailfwd.nih.gov]" > /etc/postfix/main.cf
    echo "Fixing group issue preventing db update"
    cp $code_path/docker/GroupRole.php ./web/modules/contrib/group/src/Entity
    mkdir -p config/sync
    chown -R apache:apache web
fi
