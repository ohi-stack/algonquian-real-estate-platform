# Security Policy

## Protected Data

The Buyer Portal may process buyer identity, acquisition criteria, NDA evidence, deal authorization, buyer interest, and package-download activity. Administrators must treat these records as confidential business information.

## Required Deployment Controls

- Serve the site over HTTPS.
- Store deal packages in private or access-controlled storage.
- Do not expose attachment URLs in page source or deal metadata rendered to buyers.
- Restrict `algq_manage_buyer_portal` and `algq_manage_buyer_deals` to authorized personnel.
- Review buyer assignments before publishing a deal.
- Increment the configured NDA version whenever the governing NDA changes.
- Maintain WordPress, PHP, plugins, and hosting security updates.
- Retain download and NDA records according to written company policy.

## Vulnerability Reporting

Do not disclose suspected vulnerabilities through public issues containing buyer, deal, credential, or file-location data. Report them privately to the repository administrator with reproduction steps and affected versions.

## Non-Goals

This plugin does not provide legal advice, determine whether an NDA is legally sufficient, or replace counsel review of deal-distribution and confidentiality procedures.
