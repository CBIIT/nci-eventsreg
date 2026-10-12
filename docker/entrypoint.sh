#!/bin/sh
# Start Apache in the background
postfix start
echo "Installing stable9"
drush theme:enable stable9
drush updb -y
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["content_sync"]); $config->set("module", $modules)->save();'
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_location_places"]); $config->set("module", $modules)->save();'
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_shortcuts"]); $config->set("module", $modules)->save();'    
# This below changes file storage in content types to private #
drush php:eval 'foreach (\Drupal::entityTypeManager()->getStorage("field_storage_config")->loadMultiple() as $f) { if (in_array($f->getType(), ["image", "file"], TRUE)) { $f->setSetting("uri_scheme", "private"); $f->save(); echo $f->id() . " => private\n"; } }'
drush en legacy_files
drush en s3fs
su -s /bin/sh apache -c 'cd /var/www/drupal && php -d memory_limit=1G vendor/drush/drush/drush.php cr'
httpd -D FOREGROUND &
wait
