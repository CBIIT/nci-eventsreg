#!/bin/sh
echo "running entity updates"
chown -R apache:apache ../
drush --root=.. updatedb-status --entity-updates
drush --root=.. php-eval "\$m=\Drupal::entityDefinitionUpdateManager();foreach(['publish_on','unpublish_on', 'filter_image_lazy_load'] as \$f){\$d=\$m->getFieldStorageDefinition(\$f,'taxonomy_term');if(\$d){\$m->uninstallFieldStorageDefinition(\$d);print\"Removed \$f\n\";}else{print\"\$f not found\n\";}}"
echo "copy composer"
cp composer.json ../
echo "removing composer lock"
rm ../composer.lock
echo "setting git safe directories"
git config --global --add safe.directory /var/www/drupal/web/modules/contrib/authorization
git config --global --add safe.directory /var/www/drupal/web/modules/contrib/security_review
git config --global --add safe.directory /var/www/drupal/web/modules/contrib
echo "removing swiftmailer, webform_mass_email, security_review, rules integration, rules"
drush --root=.. pmu swiftmailer webform_mass_email security_review scheduler_rules_integration rules nodeaccess -y
drush --root=.. en symfony_mailer ckeditor5 -y
echo "running composer"
composer --working-dir=.. install
echo "copying fixes for redirect subscriber and element"
cp RedirectSubscriber.php ../web/modules/contrib/url_redirect/src/EventSubscriber
cp Element.php ../web/core/lib/Drupal/Core/Render
echo "clearing cache"
cp eventsreg.info.yml ../web/modules/custom/eventsreg/ 
cp eventsreg.module ../web/modules/custom/eventsreg/
cp cct.info.yml ../web/themes/cct/
echo "ADD THE MAILER CONFIGURATION"
drush --root=.. updatedb -y
drush cr
