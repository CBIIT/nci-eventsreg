export COMPOSER_MEMORY_LIMIT=-1;
echo "Loading the old drupal 8 db, remove this on stage/prod as we don't want to re-load db"
mysql -hnciws-d1066-c.nci.nih.gov -uuser -ppassword tempevents < /local/drupal/events-prod-db-rds-03162023.1258.sql 
echo "backing up old site to nci-cct-eventsreg_backup"
#mv nci-cct-eventsreg nci-cct-eventsreg_backup
# remove this line below
chmod u+w nci-cct-eventsreg/web/sites/default
rm -rf nci-cct-eventsreg

echo "Cloning drupal 8 from github"
git clone https://github.com/cbiit/nci-eventsreg nci-cct-eventsreg
cd nci-cct-eventsreg
git checkout upgrade
echo "Copying existing files from old site to new site"
cp -R ../nci-cct-eventsreg_backup/web/sites/default/files ./web/sites/default/files
cp ../nci-cct-eventsreg_backup/web/sites/default/settings.php ./web/sites/default
echo "Running composer install"
composer install
echo "Upgrading"
drush ucrt shaun
drush urol administrator shaun
drush upwd shaun 1234

drush sql-query "UPDATE users_field_data SET ldap_user_current_dn=NULL,ldap_user_last_checked=NULL,ldap_user_ldap_exclude=NULL;"
#drush pmu -y ldap_help ldap_query ldap_authorization ldap_authentication ldap_query backup_migrate
drush pmu -y ldap_help backup_migrate
composer remove drupal/backup_migrate drupal/ldap drupa/ldap_help drupal/ldap_authentication --no-update
composer require drupal/jquery_ui_checkboxradio drupal/webform:^6.1.3 drupal/admin_toolbar:^3 drupal/imce:^2.4 --no-update
composer remove --dev webflo/drupal-core-require-dev --no-update
composer remove drupal/webform_views:^5.0 drupal/entity_print:^2.7 drupal/core drupal/ldap drupal/devel drupal/console drupal/core webflo/drupal-finder drupal-composer/drupal-scaffold --no-update
composer require drupal/webform_views:^5.0 dompdf/dompdf:^2.0.0 drupal/entity_print:^2.7 drupal/default_content_deploy:^2.0 drupal/default_content:^2.0@alpha drupal/ldap cweagans/composer-patches dr
upal/core-recommended:^9 drupal/module_missing_message_fixer drush/drush:^10.0.0 drupal/backup_migrate:^5 drupal/core-composer-scaffold:^9 drupal/core-project-message:^9 --update-with-dependencies -
-no-update
composer update
drush updb -y
drush en backup_migrate

patch -u composer.json -i composer_redirect.patch
composer install
drush updb -y
echo "* Enable ldap_authentication"
drush pm-enable ldap_authentication -y
drush updb -y
drush cset ldap_authentication.settings sids.eventsldap eventsldap -y
drush cset ldap_authentication.settings skipAdministrators 0 -y
drush updb -y
drush cr
cp WebformScheduledEmailManager.php ./web/modules/contrib/webform/modules/webform_scheduled_email/src
echo "Done"
