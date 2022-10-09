#!/bin/bash

# This script will test if you have given a leap year or not.

echo "Type the admin user password:"

read ADMIN_PASS

echo "Type the test user password:"

read TEST_PASS
echo ""
echo "IMPORT PRODUCTION DATABASE FIRST"
echo "SYNTAX: drush sql-cli < backup-<date>.mysql"
echo ""

echo ""
echo "*Change user 1 name to admin"
echo "drush sql-query update users_field_data set name='admin' where uid=1;"
drush sql-query "update users_field_data set name='admin' where uid=1;"

echo ""
echo "*Sanitize Database"
echo "drush sql-sanitize -y"
drush sql-sanitize -y

echo ""
echo "*Remove webform submissions"
echo "drush webform:purge --all -y"
drush webform:purge --all -y

echo ""
echo "*Clear Cache"
echo "drush cr"
drush cr

echo ""
echo "*** COMPLETED"
echo ""
echo "*TODO: Manually change the password of users with the drush user-password command"
echo "Change PASSWORD for the folowing users:"
echo ""
echo "drush user-password admin <password>"
drush user-password admin $ADMIN_PASS
echo "drush user-password cct_user <password>"
drush user-password cct_user $TEST_PASS
echo "drush user-password nob_user <password>"
drush user-password nob_user $TEST_PASS
echo "drush user-password next_user <password>"
drush user-password next_user $TEST_PASS

echo ""
echo "*TODO: dump database"
echo "drush sql-dump > database.sql"
drush sql-dump > ../database.sql
cd ..
zip database.zip database.sql
rm database.sql
echo ""

echo "*TODO: Remove backup-<date>.mysql file"
echo "rm backup-<date>.mysql"
echo ""

echo "Push changes to github"

