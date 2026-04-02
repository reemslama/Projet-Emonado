ARG MYSQL_VERSION=8.0-debian
FROM mysql:${MYSQL_VERSION}

# Install tzdata so mysql_tzinfo_to_sql can load named timezones
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y tzdata \
    && rm -rf /var/lib/apt/lists/*

# Copy dev configuration into image with safe permissions
COPY conf.d/ /etc/mysql/conf.d/
RUN chmod 644 /etc/mysql/conf.d/*.cnf

# Copy init scripts (timezone loader etc.)
COPY initdb.d/ /docker-entrypoint-initdb.d/
RUN chmod +x /docker-entrypoint-initdb.d/*.sh

# Keep default entrypoint/cmd from mysql image
