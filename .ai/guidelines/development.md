# Local Development

- This package is developed with Orchestra Testbench, not a full Laravel app.
- `artisan` at the repo root is a symlink to `vendor/bin/testbench`, so `php artisan <command>` boots the Testbench
  skeleton with this package's service provider and the `workbench/` app.
- Run the test suite with `composer test` or `./vendor/bin/pest`.
- Serve the workbench app with `composer serve`.
- The AI tooling overrides for Boost live in `workbench/app/Support/` and are wired in
  `Workbench\App\Providers\WorkbenchServiceProvider`. They point Boost at the package root instead of the Testbench
  skeleton.
- Regenerate `CLAUDE.md` and `AGENTS.md` after editing files in `.ai/guidelines/` with `composer boost:refresh`.

## Verification

- Git hooks enforce the gate automatically. `composer install` points `core.hooksPath` at `.githooks/`; if the hooks are
  not active, run `composer install` (or `git config core.hooksPath .githooks`) once.
  - **pre-commit** auto-formats staged PHP (Pint).
  - **pre-push** runs the full CI-equivalent gate: `composer check` (Pint, PHPStan, Rector, Pest).
- Never push on red. Use `git commit`/`git push --no-verify` only in emergencies.

## Comments

- Code must be self-explanatory: reach for clear names, small functions, and types before a comment.
- Do not add comments. A comment is a last resort and explains only *why* something is done, never *what* the code does.
- When you encounter an obsolete, redundant, or "what" comment, delete it.
- Delete section banners and navigation comments unless they explain a non-obvious boundary.
- Delete comments that narrate the next line, assertion, or obvious test setup; prefer clearer test names and variable names.
- Keep PHPDoc only when it carries type information, public API intent, static-analysis value, or a non-obvious constraint.
- Keep comments that explain framework quirks, ordering requirements, cache behavior, performance traps, or other
  constraints that are hard to infer from the code alone.

## Testing

- Prefer feature tests for backend behavior. Test the package through its public API — the registry, dispatcher,
  repository bindings, and database effects — rather than isolating internals by default.
- Use unit tests only for complex algorithms implemented as pure functions or small deterministic value objects where
  integration coverage would make the important cases hard to see.
