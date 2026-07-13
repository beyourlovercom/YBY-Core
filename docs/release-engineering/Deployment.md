# Deployment

## Scope

Deployment is intentionally outside release preparation.

This repository prepares verifiable artifacts. It does not automatically upload or activate them on WordPress.

## Approved future deployment path

1. Validate the RC artifact on a controlled WordPress environment.
2. Publish an official GitHub Release from the validated commit or tag.
3. Use the official released ZIP as the only approved production package.
4. Upload and activate through the approved WordPress operations flow.

## Never deploy from

- an unverified local ZIP
- an unreviewed feature branch
- a historical plugin copy in another repository
