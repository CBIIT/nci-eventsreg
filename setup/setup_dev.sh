#!/bin/bash

echo "*** Setting up Environment for for Development"
echo ""
echo "Setting up cache"
scripts/cache_dev.sh
echo ""
echo "Enable Devel module"
echo ""
drush pm-enable devel -y
echo "Clearing Cache"
drush cr

