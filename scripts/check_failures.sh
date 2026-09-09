#!/usr/bin/env bash
set -e
cd /home/oyabin/Projects/Karen
./vendor/bin/sail test 2>&1 | grep "FAILED" | head -16
