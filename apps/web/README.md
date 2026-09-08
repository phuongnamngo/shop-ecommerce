# Watch — apps/web (Next.js)

Admin shell (`/admin`) dùng Laravel Sanctum SPA cookie tới `NEXT_PUBLIC_API_URL`.

## Dev

```bash
cp .env.example .env.local
pnpm install
pnpm dev
```

Mở [http://localhost:3000/admin/login](http://localhost:3000/admin/login).

Seed admin (API): `super_admin@example.com` / `password`.

## Cookie Sanctum

- Dùng **một** hostname cho tab web và API (`localhost` hoặc `127.0.0.1`), khớp `apps/api` `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `FRONTEND_URL`.
- Browser gọi thẳng API với `credentials: 'include'` (không BFF trong MVP shell).

Nếu thư mục `.next/` bị Docker để `nobody`/root-owned, project dùng `.next-local` làm `distDir` mặc định. Xóa cache Docker cũ (cần sudo):

```bash
sudo rm -rf .next
```
