#!/usr/bin/env bash
# Fase 6 — smoke test pengembalian & keluhan
set -e
cd /home/oyabin/Projects/Karen

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login pegawai@karen.test)
printf "%-44s -> " "/pegawai/bookings (modal Selesai)"
curl -s -b "$JAR" "http://localhost/pegawai/bookings" | grep -q "Selesai — Kembalikan Mobil" && echo "modal ADA" || echo "modal TIDAK ADA"
rm -f "$JAR"

JAR=$(login pengurus@karen.test)
printf "%-44s -> " "/pengurus/complaints"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/complaints"
printf "%-44s -> " "/pengurus/complaints?status=selesai"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/complaints?status=selesai"
rm -f "$JAR"

JAR=$(login pegawai@karen.test)
printf "%-44s -> " "/pengurus/complaints (pegawai 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/complaints"
rm -f "$JAR"
