echo "running entity updates"
drush/vendor/drush/drush/drush --root=.. updatedb-status --entity-updates
drush/vendor/drush/drush/drush --root=.. php-eval "\$m=\Drupal::entityDefinitionUpdateManager();foreach(['publish_on','unpublish_on', 'filter_image_lazy_load'] as \$f){\$d=\$m->getFieldStorageDefinition(\$f,'taxonomy_term');if(\$d){\$m->uninstallFieldStorageDefinition(\$d);print\"Removed \$f\n\";}else{print\"\$f not found\n\";}}"
echo "copy composer"
cp composer.json ../
echo "removing composer lock"
rm ../composer.lock
echo "setting git safe directories"
git config --global --add safe.directory /var/www/drupal/web/modules/contrib/authorization
git config --global --add safe.directory /var/www/drupal/web/modules/contrib/security_review
git config --global --add safe.directory /var/www/drupal/web/modules/contrib
echo "removing swiftmailer, webform_mass_email, security_review, rules integration, rules"
drush/vendor/drush/drush/drush --root=.. pmu swiftmailer webform_mass_email security_review scheduler_rules_integration rules nodeaccess -y
echo "running composer"
cd /local/drupal/events
composer install
drush  en symfony_mailer ckeditor5 -y
echo "copying fixes for redirect subscriber and element"
cp /local/drupal/events/upgrade/RedirectSubscriber.php /local/drupal/events/web/modules/contrib/url_redirect/src/EventSubscriber
cp /local/drupal/events/upgrade/Element.php /local/drupal/events/web/core/lib/Drupal/Core/Render
echo "clearing cache"
cp /local/drupal/events/upgrade/eventsreg.info.yml /local/drupal/events/web/modules/custom/eventsreg/
cp /local/drupal/events/upgrade/eventsreg.module /local/drupal/events/web/modules/custom/eventsreg/
cp /local/drupal/events/upgrade/cct.info.yml /local/drupal/events/web/themes/cct/
echo "ADD THE MAILER CONFIGURATION"
drush  updatedb -y
drush cr
