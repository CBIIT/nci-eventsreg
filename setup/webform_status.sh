#!/bin/bash

echo "*** Webform Staus"
echo ""
echo ""
grep -E 'status:|open:|close:|scheduled:' config/sync/webform.webform.*
