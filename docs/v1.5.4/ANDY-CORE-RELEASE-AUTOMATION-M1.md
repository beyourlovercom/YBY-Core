# ANDY-CORE-RELEASE-AUTOMATION-M1

## Intent

Automate GitHub Release publication after the existing tagged FINAL_RELEASE build has already passed all package, metadata, SHA-256, and Ed25519 verification gates.

## Safety contract

- GitHub Actions receives `contents: write` only for the tag release workflow.
- The signing key remains only in `ANDY_CORE_UPDATE_SIGNING_SECRET`.
- Publication uses the exact eight artifacts produced by the verified tagged build.
- The workflow creates a draft Release first, verifies tag/state/asset names and package digest through GitHub API, then publishes it as stable/latest.
- If a Release for the tag already exists, automation fails closed and does not overwrite or mutate it.
- Build, checksum, metadata, signature, package-layout, and version failures occur before Release publication.
- No Production WordPress deployment is included.

## Activation boundary

This workflow change cannot retroactively alter the already-published v1.5.4 tag run. It applies to future tags after this change reaches `main`, beginning with the next release (expected v1.5.5 or later).
