# YBY Core Development Workflow

This document defines the official development workflow for YBY Core features, fixes, refactoring, and release preparation.

## Complete lifecycle

Requirement
-> Planning
-> Feature Branch
-> Development
-> Site-appropriate validation (Local-first where applicable; Dev-first for BYL V3)
-> Code Review
-> Merge
-> Release Preparation
-> Release Workflow

## Branch strategy

### Main branch

- Branch: `main`
- Purpose: production stable code, release source, and tagged versions
- Rules:
  - No direct development on `main`
  - Only reviewed and validated changes can merge

### Feature branch

- Naming: `feature/<name>`
- Examples:
  - `feature/brand-font-manager`
  - `feature/crm-webhook`
  - `feature/project-template-engine`
- Purpose: new functionality development

### Bug fix branch

- Naming: `fix/<name>`
- Examples:
  - `fix/plugin-activation-error`
  - `fix/tracking-duplicate-event`
- Purpose: bug fixes only

### Documentation branch

- Naming: `docs/<name>`
- Examples:
  - `docs/release-workflow`
  - `docs/development-standard`
- Purpose: documentation-only changes

## Development rules

Before coding, the developer must define:

- Objective
- Scope
- Files affected
- Expected behavior
- Testing method

No development should start without a clear task definition.

## Code change rules

### PHP

Follow:

- WordPress coding standards
- Existing YBY Core architecture
- Existing class naming conventions

Required:

- New classes require documentation
- Public methods require PHPDoc
- No orphan PHPDoc comments

Example:

Correct:

```php
/**
 * Load configuration.
 *
 * @return array
 */
public function load_config() {
}
```

Incorrect:

```php
 *
 * @return array
 */
public function load_config() {
}
```

## File ownership rules

### Core runtime

- Location: `inc/`
- Responsible for business logic, runtime services, and core classes

### Admin

- Location: `admin/`
- Responsible for WordPress admin UI, settings pages, and dashboard interfaces

### Public

- Location: `public/`
- Responsible for frontend behavior and public asset loading

### Modules

- Location: `modules/`
- Responsible for feature documentation, module definitions, and extension boundaries

## Version rules

Version follows Semantic Versioning:

- `MAJOR.MINOR.PATCH`

Examples:

- Patch release: `1.1.1 -> 1.1.2`
- Minor release: `1.1.1 -> 1.2.0`
- Major release: `1.x.x -> 2.0.0`

## Commit message standard

Commit format:

- `type: description`

Allowed types:

- Feature: `feat:`
- Bug fix: `fix:`
- Documentation: `docs:`
- Refactor: `refactor:`
- Release: `release:`

Examples:

- `feat: add brand font manager`
- `fix: resolve activation fatal error`
- `docs: update development workflow`
- `refactor: simplify config loader`
- `release: publish YBY Core v1.1.2`

## Risk-based validation (Governance V2)

Before merge, test what the change can break. Do not omit tests merely to save tokens; do not mandate a costly whole-repo scan for every documentation or isolated UI task.

- PHP source changes: syntax-check changed PHP files and run affected unit/integration regression; use whole-repository lint for broader refactors or release gates.
- Frontend changes: inspect actual rendered UI on the target site's development/UAT environment and test behavior, browser console and responsive states.
- Admin changes: test applicable activation, configuration save and capability/permission checks.
- Payments, shipping policy, security, order state or data migrations: use dedicated negative tests, real environment compatibility evidence, safety gates and rollback.
- The **target site** determines the validation environment: BYL V3 Dev-first; YBY Bottle/Irrigation Local-first unless those projects approve a different policy.
- Required checks and branch protections are never bypassed. Absent CI or failed tests are not PASS. Owner visual/UAT acceptance stays mandatory when applicable.

## Pull request standard

Every feature branch should include:

- Description
- Testing
- Risk

The PR should explain:

- What changed
- Why changed
- How tested
- Commands executed
- Results
- Potential impact
- Rollback method

## Merge rules

Before merge into `main`:

- Code reviewed
- Testing completed
- No PHP syntax errors
- Documentation updated if needed

Forbidden:

- Direct push to `main` for feature development
- Merge without testing

## Relationship with release workflow

Development workflow controls:

- idea
- code
- review
- merge

Release workflow controls:

- main
- validation
- package
- tag
- release

Reference:

- [docs/RELEASE_WORKFLOW.md](RELEASE_WORKFLOW.md)
