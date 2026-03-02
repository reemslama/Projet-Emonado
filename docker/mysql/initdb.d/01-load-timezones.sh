#!/bin/bash
set -euo pipefail

if [ -z "${MYSQL_ROOT_PASSWORD:-}" ]; then
  echo "MYSQL_ROOT_PASSWORD not set; skipping timezone import."
  exit 0
fi

echo "Loading MySQL timezone tables..."
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" mysql
echo "Timezone tables loaded."
