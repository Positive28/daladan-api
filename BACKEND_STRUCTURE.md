# Daladan backend — jadvallar va ustunlar

OLX uslubida, **faqat reklamaga asoslangan**: e'lonlar + reklama to'lovi (Top Sotuv / Boost). Hisob-kitob va sotuvchi reytingi yo'q.

---

## 1. `users` (mavjud + yangi ustunlar)

| Ustun       | Turi        | Izoh                    |
|------------|-------------|-------------------------|
| id         | bigint      | PK                      |
| name       | string      | Ism                     |
| phone      | string      | Telefon (bog'lanish)     |
| **telegram** | string, null | Telegram (bog'lanish)  |
| role       | string      | user, admin              |
| email      | string      |                         |
| password   | string      |                         |
| timestamps |             |                         |

**Profil:** name, phone, telegram. **Profil rasmi (avatar):** Spatie Media Library — `User` model, `avatar` collection (bitta fayl).

---

## 2. `categories` va `subcategories` (mavjud)

- **categories:** name, slug, sort_order, is_active (mas: "Qishloq xo'jaligi").
- **subcategories:** category_id, name, slug, sort_order, is_active (mas: "Mevalar", "Sabzavotlar").

---

## 3. `regions` (yangi)

| Ustun      | Turi   | Izoh              |
|-----------|--------|--------------------|
| id        | bigint | PK                 |
| name_uz   | string | "Toshkent sh."     |
| slug      | string | unique             |
| sort_order| int    |                    |
| is_active | bool   |                    |
| timestamps|        |                    |

---

## 4. `cities` (yangi)

| Ustun      | Turi   | Izoh        |
|-----------|--------|-------------|
| id        | bigint | PK          |
| region_id | FK     | regions     |
| name_uz   | string |             |
| slug      | string |             |
| sort_order| int    |             |
| is_active | bool   |             |
| timestamps|        |             |

---

## 5. `ads` (mavjud + yangi ustunlar)

| Ustun                | Turi       | Izoh (frontend) |
|----------------------|------------|------------------|
| id                   | bigint     | PK               |
| seller_id            | FK users   | Sotuvchi         |
| category_id          | FK         |                  |
| subcategory_id       | FK         |                  |
| **region_id**        | FK, null   | Hudud filtri     |
| **city_id**          | FK, null   | Shahar           |
| **district**         | string, null | Tuman (mas: Parkent) |
| title                | string     | "Qizil olma"     |
| **description**      | text, null | To‘liq tavsif    |
| price                | decimal    | Narx             |
| quantity             | decimal    | Mavjud miqdor    |
| **quantity_description** | string, null | "dan ortiq"  |
| unit                 | string     | kg, dona         |
| **delivery_info**    | string, null | "Viloyat bo'ylab mavjud" |
| status               | enum       | active, sold, deleted |
| **is_top_sale**      | bool       | TOP SOTUV yorlig‘i |
| **is_boosted**       | bool       | + BOOST          |
| **boost_expires_at** | timestamp, null | Reklama tugash vaqti |
| **views_count**      | int        | Ko‘rilganlar soni |
| **expires_at**       | timestamp, null | E’lon muddati ("Mudiati o'tgan") |
| timestamps           |            |                  |

**E'lon rasmlari va videolari:** Spatie Media Library — `Ad` model, `gallery` collection (ko‘p fayl).

---

## 6. `favorites`

| Ustun   | Turi   | Izoh (Saqlanganlar) |
|--------|--------|----------------------|
| id     | bigint | PK                    |
| user_id| FK users |                      |
| ad_id  | FK ads |                       |
| timestamps |     | unique(user_id, ad_id) |

---

## 7. `promotion_plans`

| Ustun         | Turi   | Izoh (E'lonni reklama qilish) |
|---------------|--------|--------------------------------|
| id            | bigint | PK                             |
| name          | string | "Top Sotuv", "Boost"           |
| slug          | string | unique                         |
| description   | text   | Tarif tavsifi                  |
| price         | decimal| 45000, 25000                   |
| currency      | string | UZS                            |
| duration_days | int    | 7 kun                          |
| type          | string | top_sale, boost                |
| is_active     | bool   |                                |
| sort_order    | int    |                                |
| timestamps    |        |                                |

---

## 8. `ad_promotions`

| Ustun                   | Turi   | Izoh                    |
|-------------------------|--------|--------------------------|
| id                      | bigint | PK                       |
| ad_id                   | FK ads | Qaysi e’lon              |
| user_id                 | FK users | Kim to‘ladi           |
| promotion_plan_id       | FK promotion_plans |     |
| amount_paid             | decimal| To‘lov summati           |
| currency                | string |                          |
| started_at              | timestamp, null | Faollashgan vaqt   |
| expires_at              | timestamp, null | Tugash vaqti       |
| status                  | string | pending, paid, active, expired, cancelled |
| payment_transaction_id  | string, null | To‘lov tizimi ref    |
| timestamps              |        |                          |

---

## API resurslar

- **GET /api/v1/resources/regions** — viloyatlar (shaharlar nested yoki alohida).
- **GET /api/v1/resources/cities?region_id=1** — shaharlar (ixtiyoriy region bo‘yicha).

**Media (rasm/video):** Spatie Media Library — `media` va `model_has_media` jadvallari (`php artisan migrate` dan keyin package migrationlari).

Migrationlarni ishga tushirish: `composer install` (yoki `composer update`), keyin `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"`, keyin `php artisan migrate:fresh`.
