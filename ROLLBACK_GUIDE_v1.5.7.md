# Andy Core v1.5.7 Rollback Guide

Rollback target: latest compatible Andy Core code backup

Rollback remains code-only. Andy Core creates a bounded compatible code backup immediately before native update installation and restores it if post-install health validation fails.

Because database version remains 1.4.0 and 1.5.7 introduces no schema migration, rollback does not require any database downgrade. Existing inquiry data, Case IDs, and lead records remain untouched.