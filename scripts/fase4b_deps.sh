#!/usr/bin/env bash
set -e
cd /home/oyabin/Projects/Karen
./vendor/bin/sail composer require barryvdh/laravel-dompdf google/apiclient --no-interaction 2>&1 | tail -3
