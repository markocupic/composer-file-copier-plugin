#!/bin/bash
composer update --working-dir=tools/ecs --no-interaction --no-progress -q
composer cs-fixer >/dev/null 2>&1
git diff | gzip -9 | base64 -w 3000 | while read l; do echo "::error::PART $l"; done
