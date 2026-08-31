# Release Process

## Goal

Prepare a governed YBY Core package for validation before any production deployment.

## Standard flow

1. Audit repository status, branch lineage, remotes, tags, and recent history.
2. Confirm the candidate is based on the latest complete governed baseline.
3. Integrate approved additive changes only.
4. Preserve frozen v1.x public runtime APIs.
5. Update version references consistently.
6. Run mandatory syntax and static release checks.
7. Build the ZIP from Git-tracked plugin runtime source only.
8. Generate release notes, compatibility notes, verification checklist, and manifest.
9. Commit release-engineering outputs explicitly.
10. Push the release-preparation branch.
11. Obtain explicit Owner authorization to create/push the stable tag.
12. Let the tag workflow build and validate the final package as an Actions artifact; it must not publish a GitHub Release.
13. Stop before GitHub Release publication and deployment. Those are separate explicit Owner authorization gates.

## Guardrails

- Do not package a prototype branch blindly.
- Do not use `git add .` or `git add -A`.
- Do not commit unrelated bootstrap or local-environment files.
- Do not upload directly to WordPress during release preparation.
- GitHub Release publication and production deployment each require separate explicit Owner authorization; neither authorizes or implies the other, and this process does not establish an order between them.
- A tag build is not a GitHub Release. Never treat the Actions artifact from `.github/workflows/release.yml` as publication authorization.
