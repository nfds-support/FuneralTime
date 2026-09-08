#!/bin/bash
set -euo pipefail
# Swap upgraded orangetfc into funeraltime; keep prior app DB aside.
mysql -uroot -proot -e "DROP DATABASE IF EXISTS funeraltime_pre_import; CREATE DATABASE funeraltime_pre_import;"
# Rename by renaming tables is hard; use rename database via dump/rename:
# MariaDB: RENAME DATABASE is removed — use dump/restore or move tables.
mysql -uroot -proot -e "DROP DATABASE IF EXISTS funeraltime_pre_import;"
mysqldump -uroot -proot funeraltime > /tmp/funeraltime_pre_import.sql
mysql -uroot -proot -e "DROP DATABASE IF EXISTS funeraltime_pre_import; CREATE DATABASE funeraltime_pre_import CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -proot funeraltime_pre_import < /tmp/funeraltime_pre_import.sql

mysqldump -uroot -proot orangetfc > /tmp/orangetfc_upgraded.sql
mysql -uroot -proot -e "DROP DATABASE IF EXISTS funeraltime; CREATE DATABASE funeraltime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -proot funeraltime < /tmp/orangetfc_upgraded.sql

mysql -uroot -proot funeraltime -N -e "SELECT value FROM hs_hr_config WHERE name='instance.version'; SELECT COUNT(*) FROM hs_hr_employee; SELECT user_name FROM ohrm_user;"
echo "SWAP_OK"
