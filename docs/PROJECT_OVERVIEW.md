# Start API — loyiha konteksti (AI / dasturchi uchun)

Bu hujjat **Laravel JSON API** loyihasining tuzilishi, marshrutlar, autentifikatsiya va asosiy biznes qoidalarini qisqa bayon qiladi. Yangi sessiyada (masalan, Claude/Cursor) vazifa berishdan oldin shu faylni ixtiyoriy qo‘shib yuborish mumkin.

---

## 1. Maqsad

- **E’lonlar (ads)** bo‘yicha REST API: foydalanuvchi ro‘yxatdan o‘tadi, profilini boshqaradi, o‘z e’lonlarini yaratadi/tahrirlaydi.
- **Ochiq (public)** endpointlar: kategoriyalar, viloyat/tumanlar, faol e’lonlar ro‘yxati va bitta e’lon kartasi.
- **Admin** paneli API: kategoriya/subkategoriya CRUD, foydalanuvchilar (asosan oddiy `user` roli).

---

## 2. Texnologiyalar

| Qism | Tanlov |
|------|--------|
| Framework | Laravel 11, PHP 8.2+ |
| Ma’lumotlar bazasi | **PostgreSQL** (`DB_CONNECTION=pgsql`) |
| API auth | **JWT** (`tymon/jwt-auth`), guard: `api` |
| Media | **Spatie Media Library** (e’lon rasmlari/videolari `gallery` kolleksiyasi) |
| Hujjatlar | **L5 Swagger** (`darkaonline/l5-swagger`), annotatsiyalar controllerlarda |

---

## 3. URL va marshrutlar

- Barcha API marshrutlari `routes/api.php` orqali ulangan.
- Old prefiks: **`/api`** (Laravel default), ichki guruh: **`v1`**.
- To‘liq bazaviy yo‘l: **`/api/v1/...`**.

Asosiy guruhlar:

| Prefiks | Middleware | Vazifa |
|---------|------------|--------|
| `/api/v1/` | `api` | Health: `GET /` |
| `/api/v1/register`, `login`, `refresh` | `api` | JWT tokenlar |
| `/api/v1/resources/*` | `api` | `regions`, `cities`, `categories`, `subcategories` |
| `/api/v1/public/*` | `api` | Ochiq e’lonlar: `GET public/ads`, `GET public/ads/{id}` |
| `/api/v1/profile/*` | `api` + **`auth:api`** | Profil, `apiResource` **ads**, `GET ads/{ad}/stats` |
| `/api/v1/admin/*` | `api` + **`auth:api`** + **`admin`** | Kategoriyalar, subkategoriyalar, admin userlar |
| `/api/v1/logout` | `auth:api` | Chiqish |

**Muhim:** `profile` ichida `Route::get('ads/{ad}/stats', ...)` **`apiResource('ads')` dan oldin** e’lonlangan — aks holda `{ad}` bilan chalkashish bo‘lishi mumkin.

**E’lonni yangilash:** `POST profile/ads/{ad}` — `multipart/form-data` (fayl) uchun alohida; qolgan REST metodlar `apiResource` bo‘yicha.

---

## 4. Autentifikatsiya

- Login/register javobida **JWT** beriladi; keyingi so‘rovlar: `Authorization: Bearer <token>`.
- Guard: **`auth:api`** (`config/auth.php` + `tymon/jwt-auth`).
- **Admin:** `App\Http\Middleware\EnsureAdmin` (`admin` alias `bootstrap/app.php`da). Foydalanuvchi `users.role === 'admin'` bo‘lishi kerak.
- Oddiy foydalanuvchi: `User::ROLE_USER` (`'user'`).

---

## 5. JSON javob formati

`App\Mixins\ResponseFactoryMixin` orqali `Response` fabrikasiga mixin qo‘shilgan:

- **Muvaffaqiyat:** `response()->successJson($data, $status = 200)`  
  ```json
  { "success": true, "data": ..., "message": "ok" }
  ```
- **Xato:** `response()->errorJson($message, $status, $errors = null, $data = null)`  
  ```json
  { "success": false, "message": "...", "errors": ..., "data": ... }
  ```

Ayrim **Admin** controllerlar tarixan `response()->json(response()->successJson(...))` qilib ikki marta o‘ragan — yangi kodda `successJson`ni to‘g‘ridan-to‘g‘ri ishlatish afzal.

---

## 6. Domen modellari (qisqa)

- **User** — `seller_id` emas; e’lon egasi `ads.seller_id` orqali `User`ga bog‘langan.
- **Ad** — `seller_id`, `status` (`active`, `sold`, `deleted`), `views_count`, Spatie media `gallery`, `media_list` append.
- **Category / Subcategory** — ierarxiya; e’londa `category_id`, `subcategory_id`.
- **Region / City** — foydalanuvchi va e’lon kontekstida joylashuv.
- **AdView** — har bir hisoblangan ko‘rish yozuvi (statistika va tarix).
- **AdPromotion** va boshqa jadvallar — reklama/promo mantiq (kerak bo‘lsa migratsiyalardan ko‘ring).

---

## 7. E’lon ko‘rishlari (`views_count` va `ad_views`)

**Fayllar:** `App\Services\AdViewService`, `App\Models\AdView`, migratsiya `create_ad_views_table`, chaqiruv: `PublicController::ad`.

**Qoidalar (hozirgi implementatsiya):**

1. **E’lon egasi** (`auth('api')->id() === seller_id`) ochsa — hisoblanmaydi.
2. **Boshqa login yoki mehmon** — hisoblanadi.
3. **1 soat ichida** bir xil foydalanuvchi/mehmon qayta ochsa — **cache** tufayli qayta yozilmaydi (`views_count` ham oshmaydi).
4. **1 soatdan keyin** cache tugaydi — qayta ochilganda yangi `ad_views` qatori va `ads.views_count +1`.

**Cache kalitlar:**

- Login: `ad_view:{ad_id}:user_{user_id}`
- Mehmon: `ad_view:{ad_id}:ip_{ip}`

**Baza:** `ad_views` — `ad_id`, `user_id` (nullable), `ip_address`, `user_agent`, `viewed_at`. Indekslar: `(ad_id, user_id)`, `(ad_id, ip_address)`.

**`views_count` oshirish:** `DB::table('ads')->increment('views_count')` — `updated_at` avtomatik yangilanmasin (Eloquent `incrementQuietly` baribir `updated_at`ni yangilashi mumkin).

**Statistika:** `GET /api/v1/profile/ads/{ad}/stats` — faqat **shu e’lon egasi**. Javob: `total` (`ads.views_count`), `today` (joriy kun oralig‘i `startOfDay`…`endOfDay`, `config('app.timezone')` bo‘yicha), `weekly` (so‘nggi 7 kun), `monthly` (so‘nggi 30 kun) — `ad_views` ustida bitta PostgreSQL so‘rovi (`COUNT(*) FILTER`).

---

## 8. Controllerlar xaritasi

| Joy | Fayl | Rol |
|-----|------|-----|
| API | `Api/AuthController` | Register, login, refresh, logout |
| API | `Api/UserController` | Profil CRUD, avatar, parol |
| API | `Api/ResourceController` | Viloyat, tuman, kategoriya resurslari |
| API | `Api/PublicController` | Ochiq e’lonlar ro‘yxati va bitta e’lon (+ view recording) |
| API | `Api/AdsController` | Profil ostidagi e’lonlar CRUD, `viewStats` |
| Admin | `Admin/CategoryController` | Kategoriyalar |
| Admin | `Admin/SubCategoryController` | Subkategoriyalar |
| Admin | `Admin/UserController` | Foydalanuvchilar (asosan `role = user`) |

---

## 9. Konfiguratsiya eslatmalari

- `.env`: `APP_TIMEZONE` (masalan `Asia/Tashkent`), PostgreSQL `DB_*`, JWT kalitlari (`php artisan jwt:secret`).
- Deploy va VPS qadamlari qisqacha **README.md** oxirida (GitHub Actions, `ENV_CONTENT`, `migrate --force`).

---

## 10. Yangi funksiya qo‘shganda

1. Marshrutni `routes/api.php`ga qo‘shing (auth kerakmi, admin kerakmi).
2. Jadval bo‘lsa — migratsiya; PG sintaksisiga e’tibor (raw SQL bo‘lsa).
3. Javoblar — iloji boricha `successJson` / `errorJson`.
4. Swagger annotatsiyasini shu controllerdagi namunalar bo‘yicha yangilang.

---

*Oxirgi yangilanish: loyiha holati bo‘yicha `docs/PROJECT_OVERVIEW.md` (start-api).*
