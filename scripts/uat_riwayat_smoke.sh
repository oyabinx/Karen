#!/usr/bin/env bash
# Revisi riwayat & pagination — smoke live
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
H1=$(curl -s -b "$JAR" http://localhost/admin/users)
echo "users hal-1 : Sebelumnya=$(grep -c 'Sebelumnya' <<< "$H1" || true) (0 bila hal-1) | Berikutnya=$(grep -c 'Berikutnya' <<< "$H1" || true)"
rm -f "$JAR"

JAR=$(login pegawai@karen.test)
HB=$(curl -s -b "$JAR" http://localhost/pegawai/bookings)
echo "pegawai/bk  : Sebelumnya=$(grep -c 'Sebelumnya' <<< "$HB" || true) | Berikutnya=$(grep -c 'Berikutnya' <<< "$HB" || true)"
rm -f "$JAR"
