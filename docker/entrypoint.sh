#!/bin/sh

cd /var/www/drupal || exit 1

# Start Postfix.
postfix start

# Enable the Stable9 theme.
echo "Installing stable9"
drush theme:enable stable9


# Remove obsolete modules from core.extension.
drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["content_sync"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_location_places"]); $config->set("module", $modules)->save();'

drush php:eval '$config = \Drupal::configFactory()->getEditable("core.extension"); $modules = $config->get("module"); unset($modules["webform_shortcuts"]); $config->set("module", $modules)->save();'

# Set image and file field storage to private.
drush php:eval 'foreach (\Drupal::entityTypeManager()->getStorage("field_storage_config")->loadMultiple() as $f) { if (in_array($f->getType(), ["image", "file"], TRUE)) { $f->setSetting("uri_scheme", "private"); $f->save(); echo $f->id() . " => private\n"; } }'

# Prepare writable directories before database updates.
mkdir -p web/sites/default/files/asset_injector web/sites/default/private
chown -R apache:apache web/sites/default
drush pmu webform_jqueryui_buttons -y
# Only adjust Honeypot when its recorded schema is exactly 8100.
# WARNING: This skips historical updates 8101-8104; verify those migrations
# are unnecessary or have already been applied before deploying.
HONEYPOT_SCHEMA=$(drush php:eval 'echo \Drupal::keyValue("system.schema")->get("honeypot", 0);') || exit 1
if [ "$HONEYPOT_SCHEMA" = "8100" ]; then
  echo "Advancing legacy Honeypot schema 8100 to 8104"
  drush php:eval '\Drupal::keyValue("system.schema")->set("honeypot", 8104);' || exit 1
else
  echo "Honeypot schema $HONEYPOT_SCHEMA: no change"
fi

# Run database updates.

su -s /bin/sh apache -c "cd /var/www/drupal/web && drush updb -y" || exit 1

# Enable required modules.
drush en legacy_files -y
drush en s3fs -y

# Ensure Drupal's assets directory is writable.
# mkdir -p web/sites/default/assets
# chown -R apache:apache web/sites/default/assets
# chmod -R u+rwX web/sites/default/assets

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