#!/bin/sh

cd /var/www/drupal || exit 1

# Start Postfix.
postfix start

# Enable the Stable9 theme.
echo "Installing stable9"
drush theme:enable stable9

# Run database updates.
# Existing database update errors must not prevent Apache startup.
drush updb -y || echo "WARNING: Drupal database updates failed"

# Remove obsolete modules from core.extension.
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["content_sync"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_location_places"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_shortcuts"]); $config->set("module", $modules)->save();'

# Set image and file field storage to private.
drush php:eval 'foreach (\Drupal::entityTypeManager()->getStorage("field_storage_config")->loadMultiple() as $f) { if (in_array($f->getType(), ["image", "file"], TRUE)) { $f->setSetting("uri_scheme", "private"); $f->save(); echo $f->id() . " => private\n"; } }'

# Enable required modules.
drush en legacy_files -y
drush en s3fs -y

# Ensure the public Asset Injector directory is writable.
# Asset Injector's installation checks still reference public://.
mkdir -p web/sites/default/files/asset_injector
chown -R apache:apache web/sites/default/files/asset_injector

# Ensure Drupal's assets directory is writable.
mkdir -p web/sites/default/assets
chown -R apache:apache web/sites/default/assets
chmod -R u+rwX web/sites/default/assets

# Disable JavaScript aggregation on every ECS deployment.
echo "Disabling JavaScript aggregation"
drush cset system.performance js.preprocess 0 -y

# Rebuild Drupal caches.
# Asset Injector's cache-flush hook deletes its generated files.
echo "Rebuilding Drupal caches"

# su -s /bin/sh apache -c \
#   'cd /var/www/drupal && php -d memory_limit=2G vendor/drush/drush/drush.php cr'
php -d memory_limit=2G vendor/drush/drush/drush.php cr

# Explicitly regenerate Asset Injector CSS and JS.
# This runs AFTER drush cr because cache rebuilding deletes these files.
echo "Regenerating Asset Injector CSS and JS"

su -s /bin/sh apache -c 'cd /var/www/drupal && php -d memory_limit=2G vendor/drush/drush/drush.php php:eval '\''
foreach (["asset_injector_css", "asset_injector_js"] as $type) {
  $assets = \Drupal::entityTypeManager()->getStorage($type)->loadMultiple();
  foreach ($assets as $asset) {
    $storage = new \Drupal\asset_injector\AssetFileStorage($asset);
    $storage->createFile();
  }
  echo "$type: " . count($assets) . " processed\n";
}
'\'''

# Clear stale library discovery and page caches.
# Do not run drush cr again after generating the files.
echo "Clearing library discovery and page caches"

su -s /bin/sh apache -c 'cd /var/www/drupal && php -d memory_limit=2G vendor/drush/drush/drush.php php:eval '\''
\Drupal::service("library.discovery.collector")->clear();
\Drupal::service("cache.render")->deleteAll();
\Drupal::service("cache.dynamic_page_cache")->deleteAll();
\Drupal::service("cache.page")->deleteAll();
'\'''

# Report generated asset counts.
echo "Asset Injector CSS files:"
find web/sites/default/assets/asset_injector/css -name '*.css' 2>/dev/null | wc -l

echo "Asset Injector JS files:"
find web/sites/default/assets/asset_injector/js -name '*.js' 2>/dev/null | wc -l

# Start Apache.
echo "Starting Apache"
exec httpd -D FOREGROUND