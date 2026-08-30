#!/usr/bin/env bash
set -e
cd /home/oyabin/Projects/Karen
./vendor/bin/sail composer require laravel-lang/common:dev-main --dev --no-interaction 2>&1 | tail -4
./vendor/bin/sail artisan lang:add id 2>&1 | tail -2
