#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu 24.04 Droplet for LuxBet staging.
# Run as root:  bash bootstrap-droplet.sh <deploy-user> "<deploy user's SSH public key>"
#
# Installs Docker, enables the firewall (SSH, HTTP, HTTPS only), unattended
# security updates and fail2ban, creates a non-root deploy user and the
# /opt/luxbet directory. It does not copy secrets; do that by hand.
set -euo pipefail

user="${1:?deploy user name}"
pubkey="${2:?SSH public key for the deploy user}"

apt-get update
apt-get -y upgrade
apt-get install -y ca-certificates curl ufw fail2ban unattended-upgrades openssl

# Docker Engine from Docker's official repository.
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    > /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Firewall. Docker-published ports are only 80/443 (Caddy).
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
ufw --force enable

dpkg-reconfigure -f noninteractive unattended-upgrades
systemctl enable --now fail2ban

# Deploy user: SSH key only, may run docker.
id "$user" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$user"
usermod -aG docker "$user"
install -d -m 700 -o "$user" -g "$user" "/home/$user/.ssh"
echo "$pubkey" > "/home/$user/.ssh/authorized_keys"
chown "$user:$user" "/home/$user/.ssh/authorized_keys"
chmod 600 "/home/$user/.ssh/authorized_keys"

# No password or root logins over SSH.
sed -i 's/^#\?PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config
sed -i 's/^#\?PermitRootLogin .*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config
systemctl reload ssh

install -d -m 750 -o "$user" -g "$user" /opt/luxbet /opt/luxbet/scripts
install -d -m 700 -o "$user" -g "$user" /var/backups/luxbet

echo "Droplet ready. Next: copy .env and backup.env to /opt/luxbet (chmod 600) and run the first deploy."
