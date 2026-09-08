#!/bin/bash
set -euo pipefail

# Host backup already done separately if present
sed -i '1{/^\/\*M!999999/d;}' /tmp/orangetfc.sql

mysql -uroot -proot -e "DROP DATABASE IF EXISTS orangetfc; CREATE DATABASE orangetfc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -proot orangetfc < /tmp/orangetfc.sql

echo "=== import summary ==="
mysql -uroot -proot orangetfc -N -e "SELECT value FROM hs_hr_config WHERE name='instance.version';"
mysql -uroot -proot orangetfc -N -e "SELECT COUNT(*) AS employees FROM hs_hr_employee;"
mysql -uroot -proot orangetfc -N -e "SELECT COUNT(*) AS users FROM ohrm_user;"
mysql -uroot -proot orangetfc -N -e "SELECT user_name FROM ohrm_user ORDER BY id LIMIT 30;"
echo "IMPORT_OK"
