# Andy Core v1.5.6 Rollback Guide

Rollback target: latest compatible updater code backup

Rollback remains code-only. Andy Core creates a bounded compatible code backup immediately before a native update installation and restores it if post-install health validation fails. Database version remains 1.4.0 and is never rolled back by this tool.
