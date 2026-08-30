#!/usr/bin/env bash
# Fase 3 — smoke test HTTP admin (login admin@karen.test)
set -e
cd /home/oyabin/Projects/Karen

JAR=$(mktemp)
BASE=http://localhost

# ambil CSRF dari halaman login
TOKEN=$(curl -s -c "$JAR" "$BASE/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')

# login
curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "login POST -> %{http_code}\n" \
  -d "_token=$TOKEN&email=admin@karen.test&password=password" "$BASE/login"

for p in /admin/users /admin/users/create /admin/users/import /admin/users/template /admin/bidang; do
  printf "%-28s -> " "$p"
  curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "$BASE$p"
done

rm -f "$JAR"
