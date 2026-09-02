#!/usr/bin/env bash
# Ensure a working Docker daemon is running inside the Cloud Agent VM.
# Cloud Agent VMs are themselves containers, so the daemon must run without
# systemd and use the fuse-overlayfs storage driver (overlay-on-overlay is not
# permitted by the kernel). This script is idempotent: it is safe to call from
# both the install and start phases and on every boot.
set -euo pipefail

DOCKER_LOG=${DOCKER_LOG:-/tmp/dockerd.log}

install_docker() {
  # Install the Docker engine, the compose plugin and fuse-overlayfs when they
  # are not already present (e.g. on a fresh VM). Idempotent and non-interactive.
  if command -v docker >/dev/null 2>&1 && command -v fuse-overlayfs >/dev/null 2>&1; then
    return 0
  fi
  echo "Installing Docker engine, compose plugin and fuse-overlayfs..."
  export DEBIAN_FRONTEND=noninteractive
  sudo apt-get update -qq
  # --force-confold avoids interactive dpkg conffile prompts (e.g. fuse.conf).
  sudo apt-get install -y -qq -o Dpkg::Options::=--force-confold \
    docker.io docker-compose-v2 fuse-overlayfs
}

configure_daemon() {
  sudo mkdir -p /etc/docker
  # Only (re)write the config if it differs, to keep the script idempotent.
  local desired='{
  "storage-driver": "fuse-overlayfs",
  "features": { "containerd-snapshotter": false }
}'
  if [ ! -f /etc/docker/daemon.json ] || [ "$(cat /etc/docker/daemon.json)" != "$desired" ]; then
    echo "$desired" | sudo tee /etc/docker/daemon.json >/dev/null
  fi
}

wait_for_docker() {
  for _ in $(seq 1 60); do
    if sudo docker info >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  return 1
}

normalize_firewall() {
  # This VM has two active firewall backends. Docker 29 programs its rules via
  # nftables (which correctly permits user-defined compose bridges), but a stale
  # iptables-legacy FORWARD chain defaults to policy DROP and only whitelists
  # docker0. Because both hooks run, that legacy DROP silently blocks all egress
  # from compose networks (e.g. Composer cannot reach packagist). nftables still
  # enforces Docker's own isolation, and egress here is unrestricted, so relaxing
  # the legacy FORWARD policy to ACCEPT is safe and makes container egress work.
  if command -v iptables-legacy >/dev/null 2>&1; then
    sudo iptables-legacy -P FORWARD ACCEPT 2>/dev/null || true
  fi
}

install_docker

if sudo docker info >/dev/null 2>&1; then
  echo "Docker daemon already running."
  normalize_firewall
  exit 0
fi

configure_daemon

echo "Starting Docker daemon..."
sudo rm -f /var/run/docker.pid
sudo bash -c "nohup dockerd >>'$DOCKER_LOG' 2>&1 &"

if wait_for_docker; then
  echo "Docker daemon is ready."
  normalize_firewall
else
  echo "ERROR: Docker daemon failed to become ready. Recent log:" >&2
  tail -n 40 "$DOCKER_LOG" >&2 || true
  exit 1
fi
