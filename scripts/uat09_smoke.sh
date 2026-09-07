#!/usr/bin/env bash
# Tindak lanjut pasca-UAT03 (2 keputusan) — smoke
set -e
cd /home/oyabin/Projects/Karen

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$TOKEN&email=$1&password=password" http://localhost/login 2>/dev/null || true
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login admin@karen.test)
for p in /pengurus/complaints /pengurus/bookings /pengurus/reports /admin/activity-logs; do
  printf "%-30s (admin) -> " "$p"
  curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost$p"
done
printf "%-30s -> " "log terbaru menampilkan pelaku"
curl -s -b "$JAR" http://localhost/admin/activity-logs | grep -qE "Sistem \(otomatis\)|Menambah|Mengubah" && echo "ADA" || echo "cek data"
rm -f "$JAR"
JAR=$(login pegawai@karen.test)
printf "%-30s -> " "/admin/activity-logs (pegawai 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" http://localhost/admin/activity-logs
rm -f "$JAR"
