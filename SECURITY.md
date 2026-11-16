# Security Policy

## Supported versions

Security fixes are developed on `main` and published in new releases. Use the
latest stable release for security updates. Backports to older releases are not
guaranteed, including the preserved `v1.2.3` edition.

Versions after `1.2.3` contain breaking changes. Follow the
[migration guidance](README.md#changes-after-123) and test your integration before
upgrading. Please report suspected vulnerabilities in any version.

## Reporting a vulnerability

Email [anar.k.jafarov@gmail.com](mailto:anar.k.jafarov@gmail.com) privately with
the subject `TranzWare Payment Gateway security report`.

If GitHub private vulnerability reporting is enabled, you can also use **Report
a vulnerability** on the repository's
[Security Advisories page](https://github.com/Open-Payment-Solutions/TranzWarePaymentGatewayApi/security/advisories).

Do not disclose vulnerability details in public issues, pull requests, or
discussions before maintainers have had an opportunity to investigate and
coordinate a fix.

Include the following where possible:

- Affected package version or commit, PHP version, and relevant configuration.
- A description of the vulnerability, its impact, and any prerequisites.
- Reproduction steps or a minimal proof of concept using synthetic data.
- Suggested mitigations or a proposed fix, if available.

Do not include payment card data, customer information, merchant credentials,
private keys, or live payment session identifiers. Redact sensitive information
from logs and request or response examples. Use a sandbox or a system you are
authorized to test.

Maintainers will review the report and coordinate any fix and disclosure with
the reporter. Response and resolution times depend on maintainer availability
and the complexity of the issue; no fixed response time is guaranteed.

For ordinary bugs and usage questions, use the repository's public issues.
