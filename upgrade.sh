#!/bin/bash
drush sql-query "UPDATE users_field_data SET ldap_user_current_dn=NULL,ldap_user_last_checked=NULL,ldap_user_ldap_exclude=NULL;"
drush pmu -y ldap_help ldap_query ldap_authorization ldap_authentication ldap_query backup_migrate
composer remove drupal/backup_migrate drupal/ldap drupal/ldap_authentication --no-update
composer require drupal/jquery_ui_checkboxradio drupal/webform:^6.1.3 drupal/admin_toolbar:^3 drupal/imce:^2.4 --no-update
composer remove --dev webflo/drupal-core-require-dev --no-update
composer remove drupal/webform_views:^5.0 drupal/entity_print:^2.7 drupal/core drupal/ldap drupal/devel drupal/console drupal/core webflo/drupal-finder drupal-composer/drupal-scaffold --no-update
composer require drupal/webform_views:^5.0 dompdf/dompdf:^2.0.0 drupal/entity_print:^2.7 drupal/default_content_deploy:^2.0 drupal/default_content:^2.0@alpha drupal/ldap cweagans/composer-patches drupal/core-recommended:^9 drupal/module_missing_message_fixer drush/drush:^10.0.0 drupal/backup_migrate:^5 drupal/core-composer-scaffold:^9 drupal/core-project-message:^9 --update-with-dependencies --no-update
composer update
drush updb -y
drush en backup_migrate

patch -u composer.json -i composer_redirect.patch
composer install
drush updb -y
ldap_address_no_ldaps=$(echo "$ldap_address"  | sed -r 's/ldaps:\/\///g')
drush cset ldap_servers.server.nci address $ldap_address_no_ldaps -y
drush cset ldap_servers.server.nci port $ldap_port -y
drush cset ldap_servers.server.nci encryption ssl -y

echo "* Enable ldap_authentication"
drush pm-enable ldap_authentication -y
drush cset ldap_authentication.settings sids.nci nci -y
drush cset ldap_authentication.settings skipAdministrators 0 -y
drush updb -y
drush cr

