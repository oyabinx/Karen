#!/usr/bin/env bash
# Fase 4c — smoke test HTTP event armada
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
printf "%-40s -> " "/pengurus/events (pengurus)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/events"
printf "%-40s -> " "/pengurus/events/create"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/events/create"
printf "%-40s -> " "/pengurus/events/create?start&end"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/events/create?start_date=2026-12-01&end_date=2026-12-07"
rm -f "$JAR"

JAR=$(login admin@karen.test)
printf "%-40s -> " "/pengurus/events (admin)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/events"
rm -f "$JAR"

JAR=$(login pegawai@karen.test)
printf "%-40s -> " "/pengurus/events (pegawai 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/pengurus/events"
rm -f "$JAR"
