# Design System

Monochrome, mono-typed, structured — drawn from **Nothing**, **vask.dev**, and **Twenty CMS**,
and seeded from `DESIGN.md`. Built on **Tailwind v4 + shadcn/ui**. Live reference: **`/ui`**
(the gallery route), which also hosts the light/dark/system toggle.

## Principles

- **Black & white first.** The neutral ramp carries the whole UI; **color is reserved for
  state** (focus ring, error/destructive). No decorative color.
- **Mono everywhere.** **JetBrains Mono** is the system typeface (body, UI, headings).
- **Dot-matrix for highlights.** **Doto** — an OFL dot-matrix display face — is the legally-clean
  stand-in for Nothing's proprietary "Ndot". Use it *sparingly* via the `.text-display` class for
  highlight **numerals and hero accents**: scores, court numbers, countdowns, bracket seeds.
- **Tokens, not raw values.** Components must reference semantic tokens (`bg-primary`,
  `text-muted-foreground`, `border-border`), never hex. (DESIGN.md "Do".)

## Themes

Light and dark ship together with a persisted toggle (`useAppearance` → `light | dark | system`,
stored in `localStorage`, applied as a `.dark` class on `<html>`). Dark mode is **true black**
(`oklch(0 0 0)`) with primary inverted to white-on-black.

## Tokens (`resources/css/app.css`)

| Token | Light | Dark | Used for |
| --- | --- | --- | --- |
| `--background` / `--foreground` | white / near-black | true black / near-white | page surface + text |
| `--primary` | near-black | white | primary actions |
| `--muted-foreground` | `oklch(0.45)` | `oklch(0.65)` | secondary text |
| `--border` / `--input` | `oklch(0.90)` | `oklch(0.24)` | hairlines, fields |
| `--ring` | near-black | `oklch(0.83)` | focus-visible ring |
| `--destructive` | `oklch(0.55 0.22 25)` | `oklch(0.62 0.21 25)` | error/destructive only |
| `--radius` | `0.375rem` | — | corner radius (crisp, technical) |

Fonts are exposed as theme tokens: `--font-sans` / `--font-mono` (JetBrains Mono) and
`--font-display` (Doto → `font-display` utility / `.text-display`).

### Mixed-font stability

`.text-display` (Doto) sits next to JetBrains Mono, so it sets `font-size-adjust: from-font`
to normalise x-height, with a `@supports not (...)` fallback. (Per the modern-web-guidance
mixed-fonts rule.)

## Component rules (from DESIGN.md — enforced)

Every interactive component **must** define: **default · hover · focus-visible · active ·
disabled · loading · error**. The shadcn primitives already encode hover/focus/disabled; feature
components add loading/error states explicitly.

- **Keyboard-first**, visible focus rings (`focus-visible:ring-2 ring-ring`).
- Handle **long content, overflow, and empty states**.
- No one-off spacing/typography — use the scale.

## Accessibility — WCAG 2.2 AA (testable)

- Text contrast ≥ 4.5:1 (≥ 3:1 for large text) in **both** themes — the monochrome ramp is
  chosen to satisfy this; verify with axe in Playwright.
- Every interactive element is reachable and operable by keyboard with a visible focus indicator.
- Form errors are announced (`aria-invalid` + associated message), not color-only.

## QA checklist (per screen)

- [ ] Only semantic tokens used (no raw hex).
- [ ] Renders correctly in light **and** dark.
- [ ] All interactive states present (incl. loading + error).
- [ ] Keyboard-navigable; focus visible.
- [ ] axe: no AA contrast/role violations.
- [ ] Dot-matrix used only for highlight numerals/accents.
