#!/bin/sh
set -eu
umask 077
cd /certs
echo 'Generating fresh integration CA, server certificate, and client certificates.'
openssl req -x509 -newkey rsa:2048 -nodes -keyout ca.key -out ca.crt \
  -days 1 -subj '/CN=Transport integration CA' -addext 'basicConstraints=critical,CA:TRUE' 2>/dev/null
openssl req -newkey rsa:2048 -nodes -keyout server.key -out server.csr -subj '/CN=server' 2>/dev/null
printf 'subjectAltName=DNS:server\nextendedKeyUsage=serverAuth\n' > server.ext
openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial \
  -out server.crt -days 1 -extfile server.ext 2>/dev/null
openssl req -newkey rsa:2048 -passout pass:integration-only -keyout client.key \
  -out client.csr -subj '/CN=Transport integration client' 2>/dev/null
printf 'extendedKeyUsage=clientAuth\n' > client.ext
openssl x509 -req -in client.csr -CA ca.crt -CAkey ca.key -CAcreateserial \
  -out client.crt -days 1 -extfile client.ext 2>/dev/null
openssl req -x509 -newkey rsa:2048 -nodes -keyout untrusted.key -out untrusted.crt \
  -days 1 -subj '/CN=Untrusted client' 2>/dev/null
exec python /mock/server.py
