#!/bin/bash
echo "copying WebformScheduledEmailManager.php"
cp /opt/drupal/WebformScheduledEmailManager.php /opt/drupal/web/modules/contrib/webform/modules/webform_scheduled_email/src
if [ ! -d "/mnt/s3fs/public" ];then
  echo "/mnt/s3fs/public does not exist. Creating now"
  mkdir /mnt/s3fs/public
  cp -R /opt/drupal/web/sites/default/files/* /mnt/s3fs/public
  chown -R drupaldocker:drupaldocker /mnt/s3fs/public
fi

if [ ! -d "/mnt/s3fs/private" ];then
  echo "/mnt/s3fs/private does not exist. Creating now"
  mkdir /mnt/s3fs/private
  chown -R drupaldocker:drupaldocker /mnt/s3fs/private
fi

#rm -rf /opt/drupal/web/sites/default/files
#ln -s /mnt/s3fs/public /opt/drupal/web/sites/default/files
#chown -R drupaldocker:drupaldocker /opt/drupal/web/sites/default/files
