#!/bin/bash
set -euo pipefail

# Install timezone data so mysql_tzinfo_to_sql can load named zones.
if command -v microdnf >/dev/null 2>&1; then
  microdnf install -y tzdata >/dev/null
  microdnf clean all >/dev/null
elif command -v dnf >/dev/null 2>&1; then
  dnf install -y tzdata >/dev/null
  dnf clean all >/dev/null
elif command -v yum >/dev/null 2>&1; then
  yum install -y tzdata >/dev/null
  yum clean all >/dev/null
elif command -v apt-get >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq --no-install-recommends tzdata
  apt-get clean
  rm -rf /var/lib/apt/lists/*
fi

# Fix permissions so MySQL reads our dev config.
chmod 644 /etc/mysql/conf.d/dev.cnf || true
