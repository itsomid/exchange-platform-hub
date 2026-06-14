# CLAUDE.md

Behavioral guidelines to reduce common LLM coding mistakes. Merge with project-specific instructions as needed.

**Tradeoff:** These guidelines bias toward caution over speed. For trivial tasks, use judgment.

---

## Project Structure Notes

- This system consists of **two separate Laravel projects**:
  - `admin-panel`: used for managing the exchange (admin operations)
  - `api-service`: used for serving user-facing APIs

- Both projects are connected to **a single shared database**.

- **All database migrations are located and must be executed in the `admin-panel` project only.**
  - Do NOT create or run migrations inside `api-service`.
  - Any schema change must be implemented via `admin-panel`.

---

## UI & Design Conventions

### Tables

When building any new data table in the admin panel, follow the design pattern used in:

- `admin-panel/resources/views/dashboard/spot_order/index.blade.php` (user orders list)
- `admin-panel/resources/views/dashboard/transaction/index.blade.php` (transaction list)

### AJAX Notifications

When making AJAX requests, use **Toastify** for success/error feedback. Follow the pattern used in `admin-panel/resources/views/dashboard/bot/signals/index.blade.php`:

- Green background (`#28C76F`) for success
- Red background (`#EA5455`) for errors
- `gravity: 'top'`, `position: 'right'`, `duration: 3000` (errors: 5000)

### Currency Dropdown

Whenever a dropdown for selecting a coin/currency is needed, always use the `<x-currency-select>` Blade component:

```blade
<x-currency-select
    name="currency_id"
    :currencies="$currencies"
    :selected="old('currency_id', $model->currency_id ?? '')"
    :required="true"
    error="currency_id"
/>
```

- Component file: `admin-panel/resources/views/components/currency-select.blade.php`
- Renders a searchable Select2 dropdown with each currency's coin logo
- The consuming controller must pass a `$currencies` collection (Collection of `Currency` models)
- The component handles Select2 CSS/JS injection and error display automatically

### Filters

When building filters for any listing page, follow the filter pattern used in those same two files:

- Basic filters row at the top, always visible
- Advanced filters row hidden by default with a toggle button
- Active filter summary shown when any filter is applied
- A clear filters button and (where applicable) an export button

---

## 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

## 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

## 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

For multi-step tasks, state a brief plan:

```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

---

**These guidelines are working if:** fewer unnecessary changes in diffs, fewer rewrites due to overcomplication, and clarifying questions come before implementation rather than after mistakes.
