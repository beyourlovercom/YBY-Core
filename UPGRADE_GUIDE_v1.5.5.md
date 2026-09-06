# Andy Core v1.5.5 Upgrade Guide

Upgrade target: Andy Core 1.5.5
Database target: 1.4.0

This is the first real native-update UAT target for installations bootstrapped manually to v1.5.4.

Before checking for updates, the WordPress runtime must provide `YBY_CORE_GITHUB_TOKEN` with read-only access to the private `beyourlovercom/YBY-Core` repository. Andy Core does not persist, render, or log that token.

Use Andy Core > Settings > Updates > Check for Updates, then WordPress Plugins > Update Now. The v1.5.4 updater must verify stable release metadata, exact assets, SHA-256, Ed25519 signature, database compatibility, and package structure before installation. A bounded code backup is created before the update; post-install health must report v1.5.5.

No database migration is part of this release.
