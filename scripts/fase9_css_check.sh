#!/usr/bin/env bash
set -e
cd /home/oyabin/Projects/Karen
./vendor/bin/sail npm run build 2>&1 | tail -4
echo "--- kelas proyek kini ada? ---"
for k in bg-indigo-600 bg-emerald-600 bg-amber-100 grid-cols-4; do
  printf "%-18s: %s\n" "$k" "$(grep -c "$k" public/build/assets/app-*.css)"
done
echo "--- css termuat di halaman login ---"
CSS=$(curl -s http://localhost/login | grep -o 'href="[^"]*\.css[^"]*"' | head -1 | sed 's/href="//;s/"//')
echo "file: $CSS"
curl -s -o /dev/null -w "status: %{http_code}\n" "http://localhost$CSS"
