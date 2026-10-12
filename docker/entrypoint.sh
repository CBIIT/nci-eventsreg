#!/bin/sh

# Start Postfix.
postfix start

# Ensure the Drupal assets directories exist before any Drush commands.
mkdir -p /var/www/drupal/web/sites/default/assets/asset_injector/css
mkdir -p /var/www/drupal/web/sites/default/assets/asset_injector/js
chown -R apache:apache /var/www/drupal/web/sites/default/assets
chmod -R u+rwX /var/www/drupal/web/sites/default/assets

# Enable the Stable9 theme.
echo "Installing stable9"
drush theme:enable stable9

# Run database updates.
drush updb -y

# Remove obsolete modules from core.extension.
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["content_sync"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_location_places"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_shortcuts"]); $config->set("module", $modules)->save();'

# Set image and file field storage to private.
drush php:eval 'foreach (\Drupal::entityTypeManager()->getStorage("field_storage_config")->loadMultiple() as $f) { if (in_array($f->getType(), ["image", "file"], TRUE)) { $f->setSetting("uri_scheme", "private"); $f->save(); echo $f->id() . " => private\n"; } }'

# Enable required modules.
drush en legacy_files -y
drush en s3fs -y

# Clear stale Asset Injector library discovery definitions and page caches.
su -s /bin/sh apache -c 'cd /var/www/drupal && php -d memory_limit=2G vendor/drush/drush/drush.php php:eval "\Drupal::service(\"library.discovery.collector\")->clear(); \Drupal::service(\"cache.render\")->deleteAll(); \Drupal::service(\"cache.dynamic_page_cache\")->deleteAll(); \Drupal::service(\"cache.page\")->deleteAll();"'

# Rebuild Drupal caches as Apache.
su -s /bin/sh apache -c 'cd /var/www/drupal && php -d memory_limit=2G vendor/drush/drush/drush.php cr'

# Start Apache.
httpd -D FOREGROUND &
wait