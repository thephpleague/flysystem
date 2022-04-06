#!/bin/bash

cat <<'EOF' >> /etc/ssh/sshd_config

KexAlgorithms curve25519-sha256
Ciphers aes256-gcm@openssh.com
MACs hmac-sha2-256-etm@openssh.com
HostKeyAlgorithms ssh-ed25519

EOF
