#!/usr/bin/env python3
"""Explicitly approved P6 Dev UAT fixture. NEVER connect to WordPress MySQL."""
from __future__ import annotations
import hashlib
import os
from pathlib import Path
import shutil
import sqlite3

HOME = Path.home()
STAGE = HOME / ".byl-dev-canary" / "mail-v2-p6-uat-20261014"
DB = STAGE / "outbox.sqlite"
BACKUP = STAGE / "outbox.sqlite.rollback-copy"
COLUMNS = (
    "opaque_id", "idempotency_key", "event_hash", "payload_ref", "channel",
    "sender_key", "template_key", "template_version", "consent_snapshot_hash",
    "priority", "state", "state_version", "attempt", "max_attempts",
    "next_attempt_at", "lease_token_hash", "lease_expires_at", "diagnostic_code",
    "created_at", "updated_at",
)
SCHEMA = """CREATE TABLE outbox (
 opaque_id TEXT NOT NULL PRIMARY KEY,
 idempotency_key TEXT NOT NULL,
 event_hash TEXT NOT NULL,
 payload_ref TEXT NULL,
 channel TEXT NOT NULL,
 sender_key TEXT NOT NULL,
 template_key TEXT NOT NULL,
 template_version INTEGER NOT NULL,
 consent_snapshot_hash TEXT NULL,
 priority INTEGER NOT NULL,
 state TEXT NOT NULL,
 state_version INTEGER NOT NULL,
 attempt INTEGER NOT NULL,
 max_attempts INTEGER NOT NULL,
 next_attempt_at INTEGER NOT NULL,
 lease_token_hash TEXT NULL,
 lease_expires_at INTEGER NULL,
 diagnostic_code TEXT NOT NULL,
 created_at TEXT NOT NULL,
 updated_at TEXT NOT NULL
)"""
INSERT = "INSERT INTO outbox (" + ",".join(COLUMNS) + ") VALUES (" + ",".join("?" for _ in COLUMNS) + ")"
STATES = ("queued", "deferred", "uncertain", "permanent_failed", "accepted_by_provider")


def digest(s: str) -> str:
    return hashlib.sha256(s.encode("utf-8")).hexdigest()


def valid_private_stage() -> None:
    if not str(STAGE).startswith(str(HOME) + "/.byl-dev-canary/"):
        raise SystemExit("ISOLATED_PATH_GATE_FAILED")
    if STAGE.exists() or DB.exists() or BACKUP.exists():
        raise SystemExit("STAGE_ALREADY_EXISTS_FAIL_CLOSED")
    os.umask(0o077)
    STAGE.mkdir(mode=0o700, parents=True, exist_ok=False)
    if STAGE.is_symlink() or STAGE.resolve() != STAGE:
        raise SystemExit("SYMLINK_GATE_FAILED")


def generate() -> list[tuple]:
    rows = []
    for i in range(100):
        channel = "critical_system" if i % 25 == 0 else ("account" if i % 5 == 0 else "campaign")
        priority = {"critical_system": 0, "account": 1, "campaign": 2}[channel]
        state = STATES[i % len(STATES)]
        code = {"queued": "none", "deferred": "preacceptance_reject",
                "uncertain": "acceptance_unknown", "permanent_failed": "permanent_reject",
                "accepted_by_provider": "provider_accepted"}[state]
        rows.append((
            digest(f"p6.dev.synthetic.opaque.{i}"),
            digest(f"p6.dev.synthetic.idempotency.{i}"),
            digest(f"p6.dev.synthetic.event.{i}"),
            None, channel, "newsletter_bulk" if channel == "campaign" else "system_tx",
            "synthetic_only", 1, digest("synthetic.consent." + str(i)) if channel == "campaign" else None,
            priority, state, 0, 0 if state == "queued" else 1, 3,
            100 + i, None, None, code, "2026-10-14 00:00:00.000000", "2026-10-14 00:00:00.000000",
        ))
    return rows


def validate(db: sqlite3.Connection) -> tuple[int, int, int]:
    db.row_factory = sqlite3.Row
    columns = tuple(row["name"] for row in db.execute("PRAGMA table_info(outbox)"))
    if columns != COLUMNS:
        raise RuntimeError("P6_COLUMN_ORDER_MISMATCH")
    indexes = {row["name"]: bool(row["unique"]) for row in db.execute("PRAGMA index_list(outbox)")}
    if indexes.get("uq_idempotency_key") is not True:
        raise RuntimeError("MISSING_UNIQUE_IDEMPOTENCY")
    if not {"idx_state_priority_due", "idx_sender_state_due"} <= indexes.keys():
        raise RuntimeError("MISSING_PRIORITY_INDEX")
    n = db.execute("SELECT COUNT(*) FROM outbox").fetchone()[0]
    unique = db.execute("SELECT COUNT(DISTINCT idempotency_key) FROM outbox").fetchone()[0]
    failed = db.execute("SELECT COUNT(*) FROM outbox WHERE state IN ('deferred','permanent_failed','uncertain')").fetchone()[0]
    if n != 100 or unique != 100:
        raise RuntimeError("ROW_COUNT_OR_IDEMPOTENCY_FAILURE")
    return n, unique, failed


def exercise_guards(db: sqlite3.Connection) -> None:
    opaque = db.execute("SELECT opaque_id FROM outbox WHERE state='queued' LIMIT 1").fetchone()[0]
    db.execute("SAVEPOINT p6_synthetic_cas_test")
    try:
        first = db.execute("UPDATE outbox SET state='leased', state_version=1 WHERE opaque_id=? AND state='queued' AND state_version=0", (opaque,))
        second = db.execute("UPDATE outbox SET state='leased', state_version=2 WHERE opaque_id=? AND state='queued' AND state_version=0", (opaque,))
        if first.rowcount != 1 or second.rowcount != 0:
            raise RuntimeError("CAS_VERSION_OR_STATE_GATE_FAILURE")
        key = db.execute("SELECT idempotency_key FROM outbox LIMIT 1").fetchone()[0]
        try:
            db.execute(INSERT, (
                digest("duplicate-id-different-opaque"), key, digest("duplicate-event"), None,
                "campaign", "newsletter_bulk", "synthetic_only", 1, None, 2, "queued",
                0, 0, 3, 101, None, None, "none",
                "2026-10-14 00:00:00.000000", "2026-10-14 00:00:00.000000"
            ))
            raise RuntimeError("UNIQUE_IDEMPOTENCY_NOT_ENFORCED")
        except sqlite3.IntegrityError:
            pass
    finally:
        db.execute("ROLLBACK TO SAVEPOINT p6_synthetic_cas_test")
        db.execute("RELEASE SAVEPOINT p6_synthetic_cas_test")
    if db.execute("SELECT state_version FROM outbox WHERE opaque_id=?", (opaque,)).fetchone()[0] != 0:
        raise RuntimeError("CAS_TEST_DID_NOT_ROLLBACK")


def main() -> None:
    valid_private_stage()
    fd = os.open(str(DB), os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    os.close(fd)
    db = sqlite3.connect(str(DB))
    try:
        with db:
            db.execute(SCHEMA)
            db.execute("CREATE UNIQUE INDEX uq_idempotency_key ON outbox (idempotency_key)")
            db.execute("CREATE INDEX idx_state_priority_due ON outbox (state,priority,next_attempt_at)")
            db.execute("CREATE INDEX idx_sender_state_due ON outbox (sender_key,state,next_attempt_at)")
            db.executemany(INSERT, generate())
        n, unique, failed = validate(db)
        exercise_guards(db)
    finally:
        db.close()
    shutil.copyfile(DB, BACKUP)
    BACKUP.chmod(0o600)
    readonly = sqlite3.connect("file:" + str(DB) + "?mode=ro", uri=True)
    try:
        counts = validate(readonly)
        if counts != (n, unique, failed):
            raise RuntimeError("READ_ONLY_REOPEN_MISMATCH")
    finally:
        readonly.close()
    second = sqlite3.connect("file:" + str(BACKUP) + "?mode=ro", uri=True)
    try:
        if validate(second) != (n, unique, failed):
            raise RuntimeError("ROLLBACK_BACKUP_INTEGRITY_FAILURE")
    finally:
        second.close()
    print(f"P6_ISOLATED_DEV_SQLITE_PASS fixtures={n} unique={unique} failures={failed}")
    print("P6_CAS_AND_UNIQUE_ROLLBACK_PASS")
    print("P6_BACKUP_READONLY_VERIFIED_PASS")
    print("P6_ENGINE=SQLite (MySQL migrations and concurrency NOT tested)")
    print("P6_REAL_SMTP=OFF WORKER=OFF WP_DB_WRITES=0")


if __name__ == "__main__":
    main()
