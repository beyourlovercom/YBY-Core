# Andy Core v1.5.1 Performance Report

## Automated evidence

The available CI run validates PHP lint, PHP harnesses, and JavaScript harnesses. It does not provision WordPress/MySQL or synthetic datasets, so no query timings or memory figures are claimed here.

## Required Dev measurements

Owner UAT must run the list, search, filter, detail, and 50-activity scenarios with synthetic datasets of 1,000, 10,000, and 50,000 Leads. Record query count, elapsed time, peak memory, indexed ordering, and whether any N+1 query occurs. The expected invariants are one bounded list query plus one count query, one detail query plus one bounded activity query, zero frontend Inbox SQL, zero frontend Inbox assets, and zero management writes during Lead submission.
