#!/usr/bin/env bash
# Fase 4b — smoke test HTTP (dipanggil via heredoc newgrp docker)
set -e
cd /home/oyabin/Projects/Karen

VID=$(./vendor/bin/sail artisan tinker --execute='print(App\Models\Vehicle::min("id"));' 2>/dev/null)
MID=$(./vendor/bin/sail artisan tinker --execute='print(App\Models\Maintenance::min("id"));' 2>/dev/null)
echo "vehicle_id=$VID maintenance_id=$MID"

login() {
  local jar; jar=$(mktemp)
  local token
  token=$(curl -s -c "$jar" http://localhost/login | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$jar" -c "$jar" -o /dev/null -d "_token=$token&email=$1&password=password" http://localhost/login
  echo "$jar"
}

JAR=$(login pengurus@karen.test)
for p in "/pengurus/vehicles/$VID/budgets" "/pengurus/documents" "/pengurus/maintenances/$MID/costs"; do
  printf "%-46s -> " "$p"
  curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost$p"
done
printf "%-46s -> " "/admin/integrasi/google (pengurus 403)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/admin/integrasi/google"
rm -f "$JAR"

JAR=$(login admin@karen.test)
printf "%-46s -> " "/admin/integrasi/google (admin)"
curl -s -b "$JAR" -o /dev/null -w "%{http_code}\n" "http://localhost/admin/integrasi/google"
rm -f "$JAR"
