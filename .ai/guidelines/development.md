# Local Development

- Serve the workbench app with `composer serve`.
- Regenerate `CLAUDE.md` and `AGENTS.md` after editing files in `.ai/guidelines/` with `composer boost:refresh`.

Comment style, git and pull request conventions, and the general Testbench facts (`artisan`,
`base_path()` versus `package_path()`, `testbench.yaml`, `workbench/`) come from the
`bambamboole/extended-testbench` guideline. Do not restate them here.

## Verification

- Git hooks enforce the gate automatically. `composer install` points `core.hooksPath` at `.githooks/`; if the hooks are
  not active, run `composer install` (or `git config core.hooksPath .githooks`) once.
  - **pre-commit** auto-formats staged PHP (Pint).
  - **pre-push** runs the full CI-equivalent gate: `composer check` (Pint, PHPStan, Rector, Pest).
- Never push on red. Use `git commit`/`git push --no-verify` only in emergencies.

## Testing

- Prefer feature tests for backend behavior. Test the package through its public API — the registry, dispatcher,
  repository bindings, and database effects — rather than isolating internals by default.
- Use unit tests only for complex algorithms implemented as pure functions or small deterministic value objects where
  integration coverage would make the important cases hard to see.
