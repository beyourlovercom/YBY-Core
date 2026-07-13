# Release Engineering

This directory defines the permanent release engineering standard for YBY Core.

YBY Core release engineering exists to make every production plugin package:

- reproducible
- auditable
- traceable to Git history
- safe to validate before WordPress deployment

Core permanent rules:

1. The `yby-core` repository is the only active plugin source.
2. Production plugins must come from an official GitHub Release.
3. Local ZIP files are not official production artifacts unless later attached to an official GitHub Release.
4. Historical plugin copies inside other repositories are reference only.
5. Every package must be reproducible from a Git commit and, later, from an official Git tag.
6. Website code belongs only to `ybyirrigationcom`.
7. Plugin code belongs only to `yby-core`.

See the companion documents in this folder for versioning, packaging, validation, deployment, rollback, and source-of-truth policy.
