---
description: Execute a plan using TDD and checking off its Execution status
agent: build
model: opencode-go/deepseek-v4.1-flash
---

Implement the plan: $1

`$1` may be a plan folder or its `plan.md`. The plan file is `<folder>/plan.md`.

## Source of truth and resume

- The plan file is the source of truth. If context is compacted, re-read it.
- Resume from the FIRST unchecked task in the plan's `## Execution status`
  section (do not start over).
- If `$1` is empty or no plan file exists, ask the user for the plan before
  starting.

## Mandatory rules (project conventions — see AGENTS.md)

1. Before each major action, state the current task explicitly:
   `## Working: <task-id> — <title>`. This keeps the current state visible
   across compaction.
2. TDD in small phases: one behavior at a time. For each phase: write the test
   (red) -> implement the minimum (green) -> refactor -> run again.
3. ALWAYS use `php artisan make:*` to create files (migration, model,
   controller, test, request, policy, action...). Never hand-write boilerplate.
4. Run `php artisan migrate` when you create migrations.
5. Run tests: `php artisan test --compact --filter=...` (minimum necessary).
6. At the end: `vendor/bin/pint --dirty` and run the affected tests.
7. Follow project conventions: Actions pattern, Enums, Form Requests, Policies.
8. Mutation testing: apply the concept to critical points (see AGENTS.md for the
   tool). A surviving mutant means a weak test — strengthen the test, not the
   code.

## Progress tracking

- When a task is done, check it off in the plan's `## Execution status` section
  with its "done when" evidence. No separate progress file.
- Keep the plan file as the single source of truth.

## Checkpoints (stop and wait for the user)

- Stop ONLY at decision gates and phase boundaries — not after every task.
- At a gate, present Pass/Adjust/Abort and wait for the user's choice.
- Between phases, ask: "Phase done. Continue to the next phase?
  (continue / adjust / stop)".
- When a failure scenario triggers, apply the plan's recovery cascade.

## Final step

- If no plan file exists or the plan was never saved, ask the user whether they
  want to see the final result documented before creating a file.
- If a plan file exists, ensure every completed task is checked in
  `## Execution status`.
