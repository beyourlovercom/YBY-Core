# Rollback Guide

## Use this when

RC1 validation fails or a blocking regression is found.

## Roll back to

The previous verified production plugin package, not an ad hoc local folder copy.

## Steps

1. Deactivate the RC1 plugin if necessary.
2. Remove the RC1 plugin package from the validation environment.
3. Reinstall the previous verified YBY Core package.
4. Re-check:
   - settings page
   - Project Studio
   - Runtime Viewer
   - frontend runtime globals
5. Record the rollback reason and blocking defect.
