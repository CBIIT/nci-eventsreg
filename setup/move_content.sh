#!/bin/bash

echo "*** Moving Content"
echo "diff -rq ../content\ 2/ ../content\(38\)"
cp -vp $1/*$3* ../content/versions/$2
