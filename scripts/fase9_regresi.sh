#!/usr/bin/env bash
# Fase 9 — regresi penuh: seluruh smoke script fase berurutan
set -e
cd /home/oyabin/Projects/Karen
for s in scripts/fase3_smoke.sh scripts/fase4_smoke.sh scripts/fase4b_smoke.sh scripts/fase4c_smoke.sh scripts/fase5_smoke.sh scripts/fase6_smoke.sh scripts/fase8_smoke.sh; do
  echo "════════ $s ════════"
  bash "$s"
done
