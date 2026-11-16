# Real HTTP and HTTPS transport tests

Install the development dependencies with `composer install`, start Docker, and
run from the repository root:

```sh
sh tests/integration/transports/run.sh --matrix
```

This suite is separate from `composer test`. It builds a Python mock server and
a PHP 8.4 client with OpenSSL. The default run omits cURL and verifies stream
fallback; `TWPG_TEST_WITH_CURL=1` installs cURL and verifies that it is preferred.
`TWPG_TEST_STRICT_SSL=1` enables server certificate and hostname verification;
both settings default to `0`. `--matrix` runs all four combinations. To select
one configuration:

```sh
TWPG_TEST_WITH_CURL=1 TWPG_TEST_STRICT_SSL=1 sh tests/integration/transports/run.sh
```

The suite mounts the checkout
read-only, uses a private Docker network without publishing host ports, and
generates a fresh temporary CA and server/client certificates in a Docker volume
before each test run. Both signed certificates are verified before starting the
test client.
The runner removes its containers, network, and certificate volume on exit.

Checks cover XML POST bytes and headers, HTTP/HTTPS selection, trusted and
untrusted servers, hostname verification, mutual TLS with an encrypted client
key, rejected client certificates and passphrases, explicit verification opt-out,
HTTP error bodies, redirects, empty responses, interim headers, chunked bodies,
truncated bodies, connection refusals, read timeouts, the selected strictSSL mode,
the `strictSSL=false` default, and default/custom User-Agent headers. All transport
classes are exercised directly as well as through `HTTPClient`. Additional
processes check disabled URL wrappers and stream functions, disabled cURL
functions, and missing OpenSSL. A missing transport must raise
`TransportNotAvailable` with its cause; cURL must work even when stream wrappers
or the PHP OpenSSL extension are disabled. Any failed check returns a nonzero
exit status.

The ordinary PHPUnit suite uses simulated streams and does not make network
requests, start Docker, or run this script.

The `PHP CI` GitHub Actions workflow runs four separate Docker integration jobs
for cURL/streams and strictSSL enabled/disabled on pushes and pull requests. They install
locked dependencies, start the mock server, run these checks, and clean up the
containers and temporary certificate volume.
