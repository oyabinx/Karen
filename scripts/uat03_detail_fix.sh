#!/usr/bin/env bash
# Perbaikan tombol Detail — verifikasi lingkup Alpine via HTML render
set -e
cd /home/oyabin/Projects/Karen

JAR=$(mktemp)
TOKEN=$(curl -s -c "$JAR" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$JAR" -c "$JAR" -o /dev/null -d "_token=$TOKEN&email=pengurus@karen.test&password=password" http://localhost/login

HTML=$(curl -s -b "$JAR" http://localhost/pengurus/vehicles)
rm -f "$JAR"

echo "kartu dengan x-data detail : $(grep -c 'x-data="{ detail' <<< "$HTML")"
echo "tombol Detail (@click)     : $(grep -c '@click="detail' <<< "$HTML")"

POS_XDATA=$(grep -bo 'x-data="{ detail' <<< "$HTML" | head -1 | cut -d: -f1)
POS_BTN=$(grep -bo '@click="detail' <<< "$HTML" | head -1 | cut -d: -f1)
echo "x-data($POS_XDATA) mendahului tombol($POS_BTN): $([ "$POS_XDATA" -lt "$POS_BTN" ] && echo YA || echo TIDAK)"
