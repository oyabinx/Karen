#!/usr/bin/env bash
# Fase 1 — verifikasi data seeder (dijalankan via newgrp docker bash ...)
cd /home/oyabin/Projects/Karen || exit 1

Q() { ./vendor/bin/sail exec -T mysql mysql -usail -ppassword karen -e "$1" 2>/dev/null; }

echo "== BIDANG (kuota & jumlah seksi) =="
Q "SELECT b.name AS bidang, b.max_active_bookings AS kuota, COUNT(s.id) AS jumlah_seksi FROM bidang b LEFT JOIN seksi s ON s.bidang_id=b.id GROUP BY b.id ORDER BY b.id"

echo "== USERS =="
Q "SELECT name, role, phone, seksi_id FROM users"

echo "== VEHICLES =="
Q "SELECT name, plate_number, year, status FROM vehicles"

echo "== SEKSI =="
Q "SELECT id, bidang_id, name FROM seksi ORDER BY id"
