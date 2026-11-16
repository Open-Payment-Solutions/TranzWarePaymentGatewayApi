#!/bin/sh
set -eu
case "${1:-}" in
  --matrix)
    for curl_mode in 0 1; do
      for strict_mode in 0 1; do
        TWPG_TEST_WITH_CURL="$curl_mode" TWPG_TEST_STRICT_SSL="$strict_mode" sh "$0"
      done
    done
    echo 'All four transport/strictSSL Docker configurations passed.'
    exit 0
    ;;
  '') ;;
  *) echo 'Usage: sh tests/integration/transports/run.sh [--matrix]' >&2; exit 2 ;;
esac
case "${TWPG_TEST_WITH_CURL:-0}:${TWPG_TEST_STRICT_SSL:-0}" in
  [01]:[01]) ;;
  *) echo 'TWPG_TEST_WITH_CURL and TWPG_TEST_STRICT_SSL must be 0 or 1.' >&2; exit 2 ;;
esac
cd "$(dirname "$0")"
project="twpg-transports-$$"
echo "Docker integration: curl=${TWPG_TEST_WITH_CURL:-0}, strictSSL=${TWPG_TEST_STRICT_SSL:-0}"
cleanup() {
  docker compose -p "$project" down --volumes --remove-orphans
}
trap cleanup EXIT
trap 'exit 130' INT TERM
docker compose -p "$project" up --build --detach --wait --wait-timeout 60 server
docker compose -p "$project" exec -T server \
  openssl verify -CAfile /certs/ca.crt /certs/server.crt /certs/client.crt
docker compose -p "$project" run --build --rm --no-deps client
docker compose -p "$project" run --rm --no-deps client \
  php -d allow_url_fopen=0 tests/integration/transports/run.php --disabled-wrapper
docker compose -p "$project" run --rm --no-deps client \
  php -d disable_functions=curl_exec tests/integration/transports/run.php --disabled-curl
docker compose -p "$project" run --rm --no-deps client \
  php -d disable_functions=fopen tests/integration/transports/run.php --disabled-stream-functions
docker compose -p "$project" run --rm --no-deps client \
  php -n tests/integration/transports/run.php --missing-openssl
if [ "${TWPG_TEST_WITH_CURL:-0}" = 1 ]; then
  docker compose -p "$project" run --rm --no-deps client \
    php -n -d extension=curl.so tests/integration/transports/run.php --curl-without-openssl
fi
