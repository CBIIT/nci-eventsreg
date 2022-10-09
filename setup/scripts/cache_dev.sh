#!/bin/bash

echo "*** Turning off Cache for Dev"
echo ""
echo "Setting cache for 0 minutes"
drush cset system.performance cache.page.max_age 0 -y

echo "Setting css and js aggregation to false"
drush cset system.performance css.preprocess 0 -y

drush cset system.performance js.preprocess 0 -y
