# Design Context — Community

Generated: 2026-07-10

## App Overview
A multi-tenant SaaS for managing community organizations (built on a prayer-community
structure). Each community is independent; a user can belong to several. Domains: members
(with per-community custom fields), administrations & positions (president/treasurer/etc.
with terms), contributions & donations (monthly dues + treasurer reports), meetings & minutes
(attendance + free-text minutes), and versioned bylaws (with read acknowledgements).
Built with Laravel 13 + Inertia v3 + React 19, Tailwind v4, custom shadcn/ui components.

## Target Platform
Desktop / Web (responsive). Sidebar-shell admin dashboard that collapses to a Sheet drawer on mobile.

## Layout Patterns
- Primary: collapsible left **sidebar** (16rem expanded, 3rem icon mode) + inset content area (AppSidebarLayout).
- Alternative: top **header** bar with breadcrumbs (AppHeaderLayout).
- Settings: two-column — 12rem settings nav + main content, stacks on mobile.
- Auth: centered card, max-w-sm.
- Content grids: `grid gap-4 md:grid-cols-3`; cards `rounded-xl border shadow-sm`.
- Vertical rhythm: `space-y-6` between sections; form groups `grid gap-2`.

## Navigation
- Primary: sidebar with Lucide icons + labels; team/community switcher dropdown at top.
- Secondary: settings sub-nav (two-column), breadcrumbs in header.
- Mobile: hamburger → left Sheet drawer (w-3/4, sm:max-w-sm).
- User menu: avatar dropdown (nav-user) at sidebar footer.

## Page Types
### Dashboard
- Structure: `grid auto-rows-min gap-4 md:grid-cols-3` stat/placeholder cards over a large main panel (`min-h-[100vh] rounded-xl border`).
- Key elements: aspect-video cards, PlaceholderPattern for empty states.

### List / Index (e.g. Teams)
- Structure: page heading + primary action button (`flex justify-between`), then a list of rows (`space-y-3`, each `flex justify-between gap-4 rounded-lg border p-4`).
- Key elements: name + Badge, Tooltip-wrapped icon action buttons.

### Settings / Form
- Structure: Heading (default xl / small base) + description, stacked form sections `space-y-6`.
- Key elements: Label + Input + InputError group; Select, Checkbox, Button variants.

## Interaction Patterns
- Radix-based: Dialog (modal), DropdownMenu, Select, Checkbox, Tooltip, Sheet, Collapsible, Toggle, NavigationMenu.
- Buttons: default / destructive / outline / secondary / ghost / link; sizes sm(h-8)/default(h-9)/lg(h-10)/icon.
- Feedback: Sonner toasts (bottom-right), Alert (default/destructive), Spinner, Skeleton (animate-pulse).
- Client-side routing via Inertia; forms via Fortify.

## Content Hierarchy
- Heading component: `default` = text-xl font-semibold tracking-tight (mb-8); `small` = text-base font-medium; optional muted description (text-sm text-muted-foreground).
- Card: CardHeader/Title/Description/Content/Footer, gap-6, px-6 py-6, rounded-xl.
- Badge: rounded-md px-2 py-0.5 text-xs font-medium (default/secondary/destructive/outline).
- Body text-sm; muted text-sm text-muted-foreground.

## Color Palette
Neutral, near-monochrome palette in oklch with class-based dark mode (`.dark`). Approx hex:
- Background `#ffffff`, Foreground `#232323`.
- Primary `#333333` (dark gray) / primary-foreground `#fbfbfb`.
- Secondary / muted / accent `#f7f7f7`; muted-foreground `#8f8f8f`.
- Border / input `#eaeaea`; ring `#dddddd`; sidebar `#fbfbfb`.
- Destructive: red-orange `oklch(0.577 0.245 27.325)` ≈ `#e5484d`.
- Chart accents (chart-1..5): orange, blue, purple, yellow, green — the only chromatic accents in the system.
- Radius token `--radius: 0.625rem` (10px); md 8px, sm 6px.
Note: the base app is intentionally neutral/grayscale; color variants should elevate using the
chart accent hues (orange/blue/purple/green) sparingly over the neutral base — no invented brand colors.

## Typography
- Font family: **Instrument Sans** (400/500/600) via Bunny fonts; fallback ui-sans-serif, system-ui.
- Headings tracking-tight; labels text-sm font-medium; badges text-xs.

## Responsive
- Breakpoints: sm 640, md 768 (primary), lg 1024 (desktop nav), xl 1280, 2xl 1536.
- Sidebar hidden on mobile → Sheet drawer; settings stack `flex-col lg:flex-row`; padding p-4/p-6 → md:p-10.

## UX Conventions
- Sidebar navigation with community/team switcher for multi-tenant context.
- Index page = heading + action button + bordered row list with badges & tooltip actions.
- Forms = Label/Input/InputError stacks in constrained width (md:max-w-2xl).
- Empty states use PlaceholderPattern; loading uses Skeleton; feedback uses Sonner toasts.
- Neutral grayscale aesthetic; accents reserved for status/data.
