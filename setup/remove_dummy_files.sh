#!/bin/bash

echo "*** The Scanner or something many files that are referenced to the file_managed table."
echo "These commands will clean this up"
echo "Let's count where the total number of files:"
echo ""
echo "Total files before deletion:"
echo "SELECT count(*), filename FROM file_managed group by filename;"
drush sql-query "SELECT count(*), filename FROM file_managed group by filename;"
echo "Total files before deletion:"
drush sql-query "SELECT count(*) FROM file_managed;"

echo ""
echo "Now let's remove those dummyFile.txt"
echo "delete FROM file_managed where filename = 'dummyFile.txt';"
drush sql-query "delete FROM file_managed where filename = 'dummyFile.txt';"

echo ""
echo "Now let's remove those image.jpg"
echo "delete FROM file_managed where filename = 'image.jpg';"
drush sql-query "delete FROM file_managed where filename = 'image.jpg';"

echo ""
echo "Total files after deletion:"
echo "SELECT count(*), filename FROM file_managed group by filename;"
drush sql-query "SELECT count(*), filename FROM file_managed group by filename;"
echo "Total files after deletion:"
drush sql-query "SELECT count(*) FROM file_managed;"
