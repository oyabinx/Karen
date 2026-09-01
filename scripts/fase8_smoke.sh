#!/usr/bin/env bash
# Fase 8 — smoke test dashboard & monitoring & laporan
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
printf "%-42s -> " "/dashboard (admin: rincian+scheduler)"
curl -s -b "$JAR" http://localhost/dashboard | grep -q "Kesehatan Scheduler" && echo "OK" || echo "GAGAL"
rm -f "$JAR"

JAR=$(login pengurus@karen.test)
printf "%-42s -> " "/pengurus/bookings (monitoring)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/bookings"
printf "%-42s -> " "/pengurus/reports (laporan)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/reports"
printf "%-42s -> " "/pengurus/reports?export=1 (CSV)"
curl -s -b "$JAR" "http://localhost/pengurus/reports?export=1&with_budget=1" -o /tmp/karen-laporan.csv -w "%{http_code} "
head -1 /tmp/karen-laporan.csv && rm -f /tmp/karen-laporan.csv
printf "%-42s -> " "/pengurus/bookings (pegawai 403)"
rm -f "$JAR"
JAR=$(login pegawai@karen.test)
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/bookings"
rm -f "$JAR"
