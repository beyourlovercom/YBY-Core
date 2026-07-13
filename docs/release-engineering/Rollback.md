# Rollback

## Principle

Rollback must restore the previous verified production plugin package, not a guessed local folder state.

## Rollback inputs

- previous production plugin ZIP
- previous verified commit or tag
- rollback guide stored with the release artifact

## Standard rollback steps

1. Identify the last known good production package.
2. Disable the failing plugin version if necessary.
3. Reinstall the previous verified plugin ZIP.
4. Re-run smoke verification for admin settings, runtime globals, and lead intake behavior.
5. Document the rollback reason and blocking defect.
