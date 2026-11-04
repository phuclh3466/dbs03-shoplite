# =====================================================================
#  DBS-03 - ShopLite PHP + SQLite (F-CTF / Kubernetes)
#  Tuan thu: USER 1000 | read-only rootfs | chi ghi /tmp | 1 port | log stdout
#  Khong daemon DB -> build Kaniko-safe, khoi dong tuc thi (health-check qua ngay).
# =====================================================================
FROM php:8.2-apache

# 1. SQLite CLI (build DB) + header + extension PDO SQLite
RUN apt-get update && apt-get install -y --no-install-recommends \
        sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# 2. Apache: port 80 -> 8080 (non-root) + ServerName
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
 && sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf \
 && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 3. Apache ghi pid/log/lock vao /tmp (read-only rootfs)
ENV APACHE_RUN_DIR=/tmp/apache-run \
    APACHE_PID_FILE=/tmp/apache-run/apache2.pid \
    APACHE_LOCK_DIR=/tmp/apache-lock \
    APACHE_LOG_DIR=/tmp/apache-logs

# 4. Source web
WORKDIR /var/www/html
RUN rm -f /var/www/html/index.html
COPY src/ /var/www/html/

# 5. Thu muc uploads can ghi duoc luc runtime -> symlink sang /tmp (BUILD time)
RUN rm -rf /var/www/html/uploads && ln -s /tmp/uploads /var/www/html/uploads

# 6. Tao san DB SQLite luc BUILD (khong chay daemon nao)
COPY db/seed.sql /tmp/seed.sql
RUN sqlite3 /opt/seed.db < /tmp/seed.sql && rm -f /tmp/seed.sql

# 7. Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 8. User uid 1000
RUN useradd -u 1000 -m -s /usr/sbin/nologin appuser 2>/dev/null || true

# 9. Phan quyen cho UID 1000
RUN chown -R 1000:1000 /var/www/html /etc/apache2 /var/lib/apache2 \
        /opt/seed.db /usr/local/bin/entrypoint.sh

USER 1000
EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
