#!/usr/bin/env bash
# Akses admin ke 4 menu armada — smoke
set -e
cd /home/oyabin/Projects/Karen

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$JAR" -c "$JAR" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login 2>/dev/null || true
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login admin@karen.test)
for p in /pengurus/vehicles /pengurus/maintenances /pengurus/replacements /pengurus/documents /pengurus/events; do
  printf "%-32s (admin) -> " "$p"
  curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost$p"
done
printf "%-32s (admin) -> " "/pengurus/complaints"
curl -s -b "$JAR" -o /dev/null -w "%{http_code} (harus 403)\n" "http://localhost/pengurus/complaints"
printf "%-32s (admin) -> " "/pengurus/reports"
curl -s -b "$JAR" -o /dev/null -w "%{http_code} (harus 403)\n" "http://localhost/pengurus/reports"
rm -f "$JAR"
