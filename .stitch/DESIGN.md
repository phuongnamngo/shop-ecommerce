---
name: Apex Commerce Admin
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#434654'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#747685'
  outline-variant: '#c3c6d6'
  surface-tint: '#2355cc'
  primary: '#1f53c9'
  on-primary: '#ffffff'
  primary-container: '#406de4'
  on-primary-container: '#fefcff'
  inverse-primary: '#b4c5ff'
  secondary: '#00658f'
  on-secondary: '#ffffff'
  secondary-container: '#48bdfe'
  on-secondary-container: '#004a6b'
  tertiary: '#006947'
  on-tertiary: '#ffffff'
  tertiary-container: '#00855b'
  on-tertiary-container: '#f5fff6'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174c'
  on-primary-fixed-variant: '#003daa'
  secondary-fixed: '#c8e6ff'
  secondary-fixed-dim: '#86ceff'
  on-secondary-fixed: '#001e2e'
  on-secondary-fixed-variant: '#004c6d'
  tertiary-fixed: '#6ffbbe'
  tertiary-fixed-dim: '#4edea3'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#005236'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
  page-bg: '#f8fafc'
  card-bg: '#ffffff'
  border-subtle: '#e2e8f0'
  text-headline: '#0f172a'
  text-body: '#334155'
  text-muted: '#64748b'
  primary-light: '#ecf2ff'
  secondary-light: '#e8f7ff'
  success: '#13deb9'
  success-light: '#e6fffa'
  warning: '#f59e0b'
  warning-light: '#fef5e5'
  danger: '#fa896b'
  danger-light: '#fdede8'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.015em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.005em
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '600'
    lineHeight: 22px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.04em
  metric-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  sidebar-width: 260px
  sidebar-collapsed-width: 80px
  header-height: 70px
  gutter-xs: 0.25rem
  gutter-sm: 0.5rem
  gutter-md: 1rem
  gutter-lg: 1.5rem
  gutter-xl: 2rem
  card-padding: 1.5rem
  card-padding-sm: 1rem
  container-margin-desktop: 1.5rem
  container-margin-mobile: 1rem
---

## Brand & Style

This design system defines a modern, crisp, and high-efficiency administrative dashboard crafted specifically for ecommerce operators, analysts, and store merchants. The visual tone is precise, calm, and uncluttered, eliminating cognitive fatigue during intensive multi-hour inventory, logistics, and sales auditing workflows.

The design movement combines **Corporate Modern** with **Soft Minimalist** ergonomics:
- **Pristine surfaces:** Elevated pure-white card modules sit comfortably on an ultra-light cool slate wash (`#f8fafc`), delivering distinct separation without heavy visual noise.
- **Micro-tinted chromatic accents:** Functional status signals (sales velocity, operational alerts, inventory levels) rely on pastel-tinted containers paired with high-contrast text badges rather than heavy, oversaturated fills.
- **Friendly geometric precision:** Friendly, open-aperture geometry through clean rounded profiles (12px–16px radii), hairline slate framing, and vibrant royal blue interactive targets (`#5d87ff`) that direct operational focus immediately to primary actions.

## Colors

The palette is engineered for prolonged data consumption and fast status scanning.

- **Primary (`#5d87ff`):** An energetic modern royal blue utilized for active navigation states, primary buttons, chart trend lines, and focused input indicators.
- **Secondary (`#49beff`):** A bright sky/cyan accent used for secondary metrics, comparative chart series, and supporting highlights.
- **Tertiary & Semantic Success (`#10b981` / `#13deb9`):** Mint emerald green signaling positive delta metrics, completed shipments, and verified states.
- **Neutral (`#64748b`):** A balanced slate neutral handling structural borders, secondary labels, and metadata.
- **Backgrounds & Surfaces:** The root viewport is washed in `#f8fafc`, allowing `#ffffff` cards to stand out with razor-sharp clarity.
- **Tonal Status Containers:** Semantic states leverage 10% tinted light backgrounds (e.g., `danger-light: #fdede8` for `#fa896b`, `success-light: #e6fffa` for `#13deb9`, and `primary-light: #ecf2ff` for `#5d87ff`) to ensure warning tags and icon pill enclosures maintain soft presence without visual distraction.

## Typography

Typography is set in **Plus Jakarta Sans**, chosen for its tall x-height, contemporary geometric terminals, and excellent legibility across dense data tables, dashboard stat widgets, and administrative controls.

- **Numerics & KPI Callouts:** Use the dedicated `metric-xl` role with a bold weight (`700`) and slight negative tracking (`-0.02em`) to ensure instant numeric comprehension in analytics overviews.
- **Section Headers & Card Titles:** Set using `headline-sm` or `headline-md` at semi-bold (`600`) with high-contrast slate (`#0f172a`).
- **Hierarchy & Body Data:** Standard table rows and meta labels default to `body-md` (14px) and `body-sm` (13px) in `#334155`, avoiding pure black to soften high-density information displays.

## Layout & Spacing

The layout employs a hybrid sidebar-and-dashboard-grid architecture based on an 8px modular scale.

- **Application Shell:** A persistent, fixed 260px left sidebar collapses into an 80px icon navigation on tablet screens (`< 1024px`) and transforms into an off-canvas drawer on mobile (`< 768px`). The main view incorporates a fixed 70px header with contextual utility controls and user profile actions.
- **Dashboard Grid:** Workspaces use a 12-column responsive fluid grid. KPI cards span 3 columns on desktop (`col-span-3`), 6 columns on tablet (`col-span-6`), and 12 columns on mobile. Analytical charts and transaction lists comfortably occupy 8-column and 4-column splits.
- **Spacing Rhythm:** Internal card padding is standardized to `1.5rem` (24px) for analytical graphs and tables, tightening to `1rem` (16px) for micro-metrics. Module gaps remain uniform at `1.5rem` (24px) on desktop and `1rem` (16px) on compact viewports.

## Elevation & Depth

This design system uses a crisp, border-anchored depth model supplemented by ultra-soft, diffused ambient drop shadows to maintain a featherweight aesthetic:

- **Base Card Elevation:** Cards avoid dramatic shadow casting. They use a dual-layer approach: a clean `1px solid #e2e8f0` structural hairline border coupled with an ambient shadow: `box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02), 0 4px 12px -2px rgba(15, 23, 42, 0.04)`.
- **Interactive Card Hover:** Cards that afford drill-down navigation elevate smoothly: `box-shadow: 0 10px 25px -5px rgba(93, 135, 255, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.03)` with a `translateY(-2px)` transition.
- **Overlays, Popovers, & Dropdowns:** Filter menus, user profile dropdowns, and date pickers float above the workspace with crisp delineation: `box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04)` over a border of `#e2e8f0`.
- **Modals & Drawers:** Modal sheets use backdrop-filter blurring (`backdrop-filter: blur(4px)`) backed by a translucent scrim (`rgba(15, 23, 42, 0.4)`).

## Shapes

The design system adheres to a refined **Rounded (Level 2)** shape identity that balances structured data rigor with approachable modern aesthetics:

- **Cards & Data Modules:** Radiused consistently at `12px` (`rounded-xl` in Tailwind) or `16px` (`rounded-2xl`) for primary dashboard cards and overview widgets.
- **Buttons, Inputs, & Dropdowns:** Radiused at `8px` (`rounded-lg`) to balance rectangular form efficiency with soft corners.
- **Badges, Tags, & Status Pills:** Fully rounded at `9999px` (`rounded-full`) to create a clear visual distinction between static metrics and actionable rectangular controls.
- **Icon Feature Containers:** Enclosed within `12px` rounded squares (`rounded-xl`) or fully circular pills (`rounded-full`) with subtle pastel fills.

## Components

### Buttons
- **Primary:** Filled with `#5d87ff`, text `#ffffff`, border none, 8px radius. Active state triggers `#4570ea`. Hover casts a soft tinted shadow (`0 4px 12px rgba(93, 135, 255, 0.35)`).
- **Secondary:** Filled with `#ecf2ff`, text `#5d87ff`, border none. Hover deepens to `#dce7ff`.
- **Outline / Neutral:** Background `#ffffff`, border `1px solid #e2e8f0`, text `#334155`. Hover background `#f8fafc`.
- **Destructive:** Background `#fa896b`, text `#ffffff`. Outline variant uses `#fdede8` background with `#fa896b` text.

### Badges & Status Chips
- Height: 24px–28px, rounded-full, with padding `0.25rem 0.75rem`.
- **Success:** Background `#e6fffa`, text `#13deb9` (`font-weight: 600`).
- **Warning:** Background `#fef5e5`, text `#f59e0b` (`font-weight: 600`).
- **Critical / Danger:** Background `#fdede8`, text `#fa896b` (`font-weight: 600`).
- **Primary / Info:** Background `#ecf2ff`, text `#5d87ff` (`font-weight: 600`).

### Cards & KPI Tiles
- Pure `#ffffff` background with `1px solid #e2e8f0` border, `16px` border-radius, and padded at `1.5rem`.
- KPI metric widgets display an icon in a 44x44px pastel container at the top left/right, followed by a bold metric numeral (`metric-xl`), and an inline status badge indicating positive/negative percentage trends compared to last cycle.

### Data Tables
- Header cells: `#f8fafc` background, uppercase 11px semi-bold labels (`#64748b`), padding `12px 16px`, hairline border-bottom `1px solid #e2e8f0`.
- Body cells: Background `#ffffff`, padding `16px`, border-bottom `1px solid #f1f5f9`. Hover changes row background to `#f8fafc`.
- Avatar and thumbnail badges sized at 36px–40px with `rounded-full` or `rounded-lg`.

### Form Controls & Inputs
- Height 40px, `8px` border-radius, background `#ffffff`, border `1px solid #e2e8f0`.
- Placeholder text in `#94a3b8`.
- Focus state: Border transitions to `#5d87ff` with a matching 3px glow ring: `box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.15)`.

### Navigation Sidebar
- Background `#ffffff` with a clean `1px solid #e2e8f0` right border.
- Nav items use `8px` radius and `10px 14px` padding.
- Active nav item: Background `#5d87ff`, text `#ffffff`, icon `#ffffff`.
- Inactive nav item: Text `#334155`, transparent background. Hover state uses `#f1f5f9` with text `#0f172a`.