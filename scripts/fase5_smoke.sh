#!/usr/bin/env bash
# Fase 5 — smoke test alur peminjaman (pegawai & pengurus & admin)
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
printf "%-52s -> " "/pegawai/search (pegawai)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/search"
printf "%-52s -> " "/pegawai/search?tanggal (dengan hasil)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/search?start_date=$(date +%F)&end_date=$(date +%F)"
printf "%-52s -> " "/pegawai/bookings (riwayat)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/bookings"

VID=$(./vendor/bin/sail artisan tinker --execute='print(App\Models\Vehicle::min("id"));' 2>/dev/null)
printf "%-52s -> " "/pegawai/bookings/create?vehicle&tanggal"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/bookings/create?vehicle_id=$VID&start_date=$(date +%F)&end_date=$(date +%F)"
rm -f "$JAR"

JAR=$(login pengurus@karen.test)
printf "%-52s -> " "/pegawai/search (pengurus)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/search"
rm -f "$JAR"

JAR=$(login admin@karen.test)
printf "%-52s -> " "/pegawai/search (admin harus 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pegawai/search"
rm -f "$JAR"
