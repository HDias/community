---
description: Execute a plan from .plans/ using TDD, progress tracking and artisan commands
agent: build
model: opencode-go/deepseek-v4.1-flash
---

Implement the plan: $1

`$1` may be a plan folder or its `plan.md`. Derive the rest from it:
- plan file: `<folder>/plan.md`
- progress file: `<folder>/progress.md`

## Source of truth and resume

- The plan file is the source of truth. If context is compacted, re-read it.
- If `progress.md` exists, read it and continue from the FIRST incomplete task
  instead of starting over.
- Write state to files, not to the conversation.

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

## Progress tracking (every task)

- When a task is done, APPEND to `progress.md`: task id, status, and the
  evidence that satisfies its "done when" condition.
- Mark the task done in `plan.md` (checkbox + evidence) so the plan stays
  current.

## Execution

- Follow the plan's tasks in dependency order.
- Independent tasks (see the parallel map) MAY run via subagents to keep the
  main context small; heavy work MUST be delegated to a subagent that returns a
  short summary.
- When you hit a gate, stop and decide Pass/Adjust/Abort.
- When a failure scenario triggers, apply the plan's recovery cascade.
