#!/bin/sh
# ============================================================
#  F-CTF entrypoint - PHP + SQLite (khoi dong tuc thi, khong daemon)
#  Rootfs read-only, chi ghi /tmp, chay bang USER 1000.
# ============================================================
set -e
mkdir -p /tmp/apache-run /tmp/apache-lock /tmp/apache-logs /tmp/uploads
# Copy DB SQLite (da tao luc build) vao /tmp de ghi duoc tren read-only rootfs
cp -f /opt/seed.db /tmp/app.db
exec apache2-foreground
