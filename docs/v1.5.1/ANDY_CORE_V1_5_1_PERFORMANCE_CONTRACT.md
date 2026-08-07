# Andy Core v1.5.1 Performance Contract

- Inbox assets load only on `yby-os_page_andy-core-leads`.
- List queries select bounded columns and one left join; no N+1 management or user queries.
- Default page size is 30; maximum is 100.
- Search executes only after submit and requires two characters.
- Details select one Lead, one management row, and at most 50 activities.
- System diagnostics run only on the System Status tab and are cached for 45 seconds.
- Normal public Lead submission performs zero management or activity writes and loads zero Inbox assets.
- Synthetic performance tests must report measured timings rather than invented budgets.
