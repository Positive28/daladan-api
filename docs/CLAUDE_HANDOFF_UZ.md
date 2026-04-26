# Start API — Claude uchun texnik handoff

Ushbu hujjat Claude (yoki boshqa AI assistent)ga loyihaning hozirgi holatini tez tushuntirish uchun yozilgan.

## 1) Loyiha maqsadi

- Laravel 11 asosidagi API (OLX uslubidagi agro e'lon platformasi).
- Asosiy obyekt: e'lon (`ads`), kategoriya/subkategoriya, sevimlilar, promo tariflar.
- Auth JWT orqali ishlaydi (`auth:api`).

## 2) Texnologiyalar

- Backend: Laravel 11 (PHP)
- Auth: JWT (`tymon/jwt-auth` contractlari ishlatilgan)
- DB: migratsiyalar Laravel migrationlar orqali
- Media: Spatie Media Library (avatar va ad gallery)
- API prefiksi: `/api/v1`

## 3) Hozirgi muhim qarorlar (yangilangan)

- `users` jadvalidan quyidagilar olib tashlangan:
  - `region_id`, `city_id`
  - `telegram`, `telegram_id`
- `sessions` jadvali olib tashlangan.
- `config/session.php` default driver `file`ga o'tkazilgan (database session ishlatilmaydi).
- User joylashuvi endi user profilidan emas, reklamaga bog'liq (`ads.region_id`, `ads.city_id` saqlanib qolgan).

## 4) Asosiy auth holati (hozir)

- Endpointlar:
  - `POST /api/v1/register`
  - `POST /api/v1/login`
  - `POST /api/v1/refresh`
  - `POST /api/v1/logout` (auth talab qiladi)
  - `GET /api/v1/get-me` yo'li amalda `AuthController@me` logikasi bilan ishlatiladi (route nomlanishi va swaggerni tekshirish kerak).
- Hozirgi register oddiy (`phone + password + fname + lname`), OTP hali joriy qilinmagan.

## 5) Domen modellar

- `users`: `fname`, `lname`, `phone`, `email`, `password`, `role`
- `categories`, `subcategories`
- `ads`:
  - seller/category/subcategory bog'lanishlari
  - `region_id`, `city_id`, `district`
  - promo flaglar (`is_top_sale`, `is_boosted`, `boost_*`)
- `favorites`
- `promotion_plans`, `ad_promotions`
- `ad_views`

## 6) Route strukturasi

`routes/api.php`:

- Public:
  - `GET /api/v1/resources/*`
  - `GET /api/v1/public/ads`
  - `GET /api/v1/public/ads/{id}`
- Authlangan user (`auth:api`):
  - `profile` endpointlari (profil, avatar, password, favorites, ads CRUD)
- Admin (`auth:api` + `admin` middleware):
  - ad moderatsiya, promo tasdiq, category/subcategory/users CRUD

## 6.1) Amaldagi route ro'yxati (aniq)

Base prefix: `/api/v1`

### Public (token talab qilinmaydi)

- `GET /` — API health check
- `POST /register`
- `POST /login`
- `POST /refresh`

`/resources`:
- `GET /resources/regions`
- `GET /resources/cities`
- `GET /resources/categories`
- `GET /resources/subcategories`
- `GET /resources/promotion-plans`

`/public`:
- `GET /public/ads`
- `GET /public/ads/{id}`

### Authlangan (`auth:api`)

`/profile`:
- `GET /profile` — profil
- `PUT /profile` — profil update
- `POST /profile/avatar` — avatar update
- `PUT /profile/password` — parol update
- `GET /profile/favorites`
- `POST /profile/favorites/{ad}`
- `DELETE /profile/favorites/{ad}`

Ads (profil ichida):
- `POST /profile/ads/{ad}/promotions`
- `POST /profile/ads/{ad}` — form-data update varianti
- `GET /profile/ads/{ad}/stats`
- `GET /profile/ads`
- `POST /profile/ads`
- `GET /profile/ads/{ad}`
- `PUT /profile/ads/{ad}`
- `PATCH /profile/ads/{ad}`
- `DELETE /profile/ads/{ad}`

Auth:
- `POST /logout`

### Admin (`auth:api` + `admin`)

`/admin/ads`:
- `GET /admin/ads`
- `GET /admin/ads/{id}`
- `PATCH /admin/ads/{id}/edit`
- `PATCH /admin/ads/{id}/approve`
- `PATCH /admin/ads/{id}/reject`

`/admin/ad-promotions`:
- `GET /admin/ad-promotions`
- `PATCH /admin/ad-promotions/{promotion}/confirm`

Resource CRUD:
- `GET|POST /admin/categories`
- `GET|PUT|PATCH|DELETE /admin/categories/{category}`
- `GET|POST /admin/subcategories`
- `GET|PUT|PATCH|DELETE /admin/subcategories/{subcategory}`
- `GET|POST /admin/users`
- `GET|PUT|PATCH|DELETE /admin/users/{user}`

## 7) Muhim kod konvensiyasi

- API javoblari ko'p joyda `response()->successJson(...)` va `response()->errorJson(...)` helperlari orqali qaytariladi.
- `User` modeli `JWTSubject` implement qiladi.
- `Ad` modelida highlight/promo uchun helper va scope metodlar bor.

## 8) Hozirgi ochiq masalalar

1. OTP asosidagi telefon registratsiyasi hali yo'q.
2. Email verification flow hali to'liq yo'lga qo'yilmagan.
3. Swagger/OpenAPI hujjatlarida eski maydonlar qolgan bo'lishi mumkin (telegram/region/city user qismida).
4. `BACKEND_STRUCTURE.md` fayli amaldagi sxema bilan qisman eskirgan.

## 9) Keyingi bosqich (advanced auth uchun tavsiya)

Maqsad: OLXga yaqin onboarding (phone OTP + email verify):

1. `users`ga qo'shish:
   - `phone_verified_at`
   - `status` (`pending|active|blocked`)
   - (ixtiyoriy) `registration_type`
2. Yangi jadval: `phone_verifications`
   - `phone`, `code_hash`, `expires_at`, `attempts`, `verified_at`, `resend_count`
3. Endpointlar:
   - `POST /api/v1/auth/phone/start`
   - `POST /api/v1/auth/phone/verify`
   - `POST /api/v1/auth/register/complete`
4. Xavfsizlik:
   - OTP hash saqlash
   - rate limit (phone/ip/device)
   - single-use OTP
   - resend cooldown
5. Infra:
   - SMS yuborishni queue orqali bajarish
   - provider xatolarini log/audit qilish

## 10) Claude'dan nima so'rash kerak (tayyor prompt)

Quyidagi prompt bilan Claude'dan implementatsiya so'rash mumkin:

`Bu Laravel 11 API JWT auth bilan ishlaydi. users jadvalida fname/lname/phone/email/password/role bor, region/city/telegram yo'q. Menga phone OTP asosida advanced register flow yozib ber: migrationlar (phone_verified_at, status, phone_verifications), service layer, controller endpointlar (/api/v1/auth/phone/start, /verify, /register/complete), validation, rate-limit, va feature testlar. Mavjud auth:api va response helperlar uslubiga mos yoz.`

