#!/bin/bash

echo "This will remove current drupal database and re-create it. Are you sure you wish to continue?"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) echo "killing and removing existing database";
              docker kill mysql57;
              docker rm mysql57;
              docker run --restart always --name mysql57 -d -e MYSQL_ROOT_PASSWORD=my-secret-pw -e MYSQL_USER=mysql -e MYSQL_PASSWORd=1234 -p 3308:3306 mysql:5.7.39 --max-allowed-packet=67108864;
              echo "waiting for database to start";
              sleep 10;
              mysql -uroot -pmy-secret-pw -P3308 -h127.0.0.1 --execute='create database events';
              echo "loading database";
              mysql -uroot -pmy-secret-pw -P3308 -h127.0.0.1 events < database.sql;
              echo "Done";
              break;;
        No ) exit;;
    esac
done
