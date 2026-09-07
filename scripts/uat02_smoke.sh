#!/usr/bin/env bash
# Revisi UAT-02 — smoke
set -e
cd /home/oyabin/Projects/Karen

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login admin@karen.test)
printf "%-46s -> " "/admin/users/import (tombol Pilih File CSV)"
curl -s -b "$JAR" http://localhost/admin/users/import | grep -q "Pilih File CSV" && echo "ADA" || echo "TIDAK ADA"
printf "%-46s -> " "/admin/bidang (semua collapse)"
COUNT=$(curl -s -b "$JAR" http://localhost/admin/bidang | grep -c 'details class="bg-white rounded-xl border border-gray-200 overflow-hidden" open' || true)
echo "open=$COUNT (harus 0)"
TOKEN=$(curl -s -b "$JAR" -c "$JAR" http://localhost/admin/integrasi/google | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
printf "%-46s -> " "POST test koneksi (tanpa kunci)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" -d "_token=$TOKEN" http://localhost/admin/integrasi/google/test
rm -f "$JAR"
