#!/usr/bin/env bash
# Fase 4 — smoke test HTTP halaman pengurus (login pengurus@karen.test)
set -e
cd /home/oyabin/Projects/Karen

JAR=$(mktemp)
BASE=http://localhost

TOKEN=$(curl -s -c "$JAR" "$BASE/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "login POST -> %{http_code}\n" \
  -d "_token=$TOKEN&email=pengurus@karen.test&password=password" "$BASE/login"

for p in /pengurus/vehicles /pengurus/vehicles/create /pengurus/maintenances /pengurus/replacements; do
  printf "%-30s -> " "$p"
  curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "$BASE$p"
done

# akses admin harus 403 bagi pengurus
printf "%-30s -> " "/admin/users (harus 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "$BASE/admin/users"

rm -f "$JAR"
