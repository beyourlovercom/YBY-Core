# V160-9 — Production Gate Evidence

Date: 2026-09-18
Status: readiness audit; no Production deployment performed

## Main baseline

- origin/main: cfdbc178f2528303a43828742d02fd7c84582b6d
- This is the squash merge of PR #42.

## Docs OS production fail-close

- docs_os default is false.
- Canonical ownership requires yby_docs_os_canonical_v1=true.
- Non-local environments additionally require YBY_DOCS_OS_CANONICAL_PRODUCTION_ENABLED=true.
- Code deployment alone cannot activate Docs OS canonical takeover.

## Release governance

The frozen release contract requires separate owner gates for stable tag, GitHub Release publication, and Production deployment. The tag workflow may build and validate the signed artifact only. It must not publish a GitHub Release.

A governance drift was found during V160-9: the tag workflow contained an automatic publish-release job. This hotfix removes that job and adds a regression assertion forbidding GitHub Release publication from the tag workflow.

## Signing / rollback readiness

- Final tag build requires ANDY_CORE_UPDATE_SIGNING_SECRET.
- Final package requires update-metadata.sig.
- Build must report Ed25519 Signature: PASS.
- SHA256 and updater metadata are verified.
- v1.5.7 updater backward compatibility remains in the final tag build.
- v1.6.0 introduces no database migration.
- Docs canonical ownership can be disabled before plugin rollback.
- Previous verified production package remains the rollback source.

No stable tag, GitHub Release publication, or Production upload has been performed by this gate.
