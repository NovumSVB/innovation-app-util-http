#!/usr/bin/env bash
sudo docker run -it --rm --name certbot \
      -v "$(pwd)/data/certbot:/data/certbot" \
      -v "$(pwd)/data/certbot:/etc/letsencrypt" \
      -v "/var/lib/letsencrypt:/var/lib/letsencrypt" \
      -p 80:80 \
      certbot/certbot certonly \
      --standalone \
      --preferred-challenges http \
      -d home.demo.novum.nu \
      --agree-tos \
      -m anton@novum.nu
