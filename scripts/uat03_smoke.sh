#!/usr/bin/env bash
# Revisi UAT-03 — smoke
set -e
cd /home/oyabin/Projects/Karen

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login pengurus@karen.test)
printf "%-48s -> " "/pengurus/vehicles (tombol Detail)"
curl -s -b "$JAR" http://localhost/pengurus/vehicles | grep -q "Detail Kendaraan" && echo "ADA" || echo "TIDAK"
printf "%-48s -> " "/dashboard (menu Penggantian Mobil)"
curl -s -b "$JAR" http://localhost/dashboard | grep -q "Penggantian Mobil" && echo "ADA" || echo "TIDAK"
rm -f "$JAR"

JAR=$(login pegawai@karen.test)
printf "%-48s -> " "/pegawai/search (seksi Maintenance label)"
curl -s -b "$JAR" "http://localhost/pegawai/search?start_date=$(date -d '+5 days' +%F)&end_date=$(date -d '+6 days' +%F)" | grep -q "Sedang Maintenance" && echo "ADA" || echo "BELUM (butuh data maintenance dev)"
rm -f "$JAR"
