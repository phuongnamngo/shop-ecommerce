# SITE.md — Atelier Commerce Admin

## 1. Core Identity

- **Project Name:** Ecommerce web / Atelier Commerce
- **Stitch Project ID:** `488978466418677770`
- **Mission:** Operate catalog, orders, customers, and inventory for a luxury retail storefront from a single admin console.
- **Target Audience:** Store operators, catalog merchandisers, fulfillment staff.
- **Voice:** Precise, calm, enterprise — high-signal operational copy, not marketing fluff.

## 2. Visual Language

- **Vibe:** Modern SaaS admin, blue primary (`#1f53c9` / `#5d87ff`), ice canvas (`#f8f9ff` / `#f8fafc`), white cards, Plus Jakarta Sans.
- **Auth screens:** Dark navy cards (`#192231` / `#1e293b`) with brand-blue CTAs.
- **Motion:** 150–250ms ease-out; hover lift on cards; `scale(0.99)` on press; respect `prefers-reduced-motion`.

## 3. Architecture

- App Router admin lives in `apps/web/src/app/admin`.
- Shared UI in `apps/web/src/components/admin`.
- Data via existing TanStack Query + `/api/v1/admin/*` clients. Do not replace API contracts.
- Stitch artifacts: `.stitch/designs/{screen}.html|.png`.

## 4. Live Sitemap

- [x] `/admin` — Dashboard
- [x] `/admin/catalog/products` — Products (modals for add/edit/delete)
- [x] `/admin/catalog/categories` — Categories (modals)
- [x] `/admin/catalog/brands` — Brands (modals)
- [x] `/admin/orders` — Orders (detail overlay + dedicated `/admin/orders/[id]`)
- [x] `/admin/customers` — Customers directory
- [x] `/admin/customers/[id]` — Customer 360
- [x] `/admin/inventory` — Inventory & stock
- [x] `/admin/login` — Sign in
- [x] `/admin/forgot-password` — Reset password request
- [x] `/admin/reset-password` — Token reset (existing API flow; not a Stitch screen)

## 5. Roadmap

**High:** Visual fidelity of shell, dashboard widgets, catalog modals, auth screens.  
**Medium:** Order detail modal, customer 360 sections, inventory KPIs.  
**Low:** Settings/Analytics/Reports (present in Stitch chrome, no backend).

## 6. Creative Freedom

- Match Stitch screenshots. Do not invent a third design system.
- Do not fake SSO, charts without data, or Settings routes.
- Prefer live API numbers inside Stitch layouts.
