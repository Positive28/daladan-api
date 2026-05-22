# Postman: reklama (e'lon) qo'shish — to'liq oqim

Bazaviy URL: `http://localhost/api/v1` (serverda: `https://api.daladan.uz/api/v1`)

---

## 0. Tayyorgarlik

```bash
php artisan migrate
php artisan db:seed          # admin: +998901234567 / admin12345
php artisan storage:link     # media URL uchun
```

---

## 1. Admin: kategoriya va subkategoriya daraxti

### Login (admin)

`POST /login`

```json
{
  "identifier": "+998901234567",
  "password": "admin12345"
}
```

Javobdan `data.token` ni Postman **Authorization → Bearer Token** ga qo'ying.

### Kategoriya yaratish

`POST /admin/categories` — `multipart/form-data`

| Key | Qiymat |
|-----|--------|
| name | Hayvonlar |
| slug | hayvonlar |
| is_active | 1 |
| sort_order | 1 |
| icon | (ixtiyoriy SVG fayl) |

### Root subkategoriya (1-daraja)

`POST /admin/subcategories`

```json
{
  "category_id": 1,
  "name": "Qoramol",
  "slug": "qoramol",
  "is_active": true,
  "sort_order": 1
}
```

`parent_id` yuborilmaydi yoki `null` — bu root.

### Ichki subkategoriya (2-daraja, e'lon uchun leaf bo'lishi mumkin)

`POST /admin/subcategories`

```json
{
  "category_id": 1,
  "parent_id": 1,
  "name": "Sut sigir",
  "slug": "sut-sigir",
  "is_active": true,
  "sort_order": 1
}
```

Agar yana 3-daraja kerak bo'lsa — `parent_id` = 2-daraja ID.

**Qoida:** e'lon faqat **leaf** subkategoriyada — `has_children: false` / `is_leaf: true`.

---

## 2. Sotuvchi: subkategoriyani tanlash (tokensiz)

### Kategoriyalar

`GET /resources/categories`

### Root subkategoriyalar

`GET /resources/subcategories?category_id=1`

Javob namunasi:

```json
[
  {
    "id": 1,
    "category_id": 1,
    "parent_id": null,
    "name": "Qoramol",
    "has_children": true,
    "is_leaf": false
  }
]
```

### Ichki subkategoriyalar

`GET /resources/subcategories?parent_id=1`

`is_leaf: true` bo'lgan ID ni e'lon uchun ishlating.

---

## 3. Sotuvchi: e'lon yaratish

### Login (oddiy user yoki admin sifatida sotuvchi)

Telefon OTP yoki email orqali ro'yxatdan o'tgan user tokeni kerak.

### Yaratish

`POST /profile/ads` — **multipart/form-data**

| Key | Majburiy | Misol |
|-----|----------|-------|
| category_id | ha | 1 |
| subcategory_id | ha | 2 (faqat leaf ID) |
| title | ha | Sut sigir sotiladi |
| description | yo'q | ... |
| price | yo'q | 15000000 |
| region_id | yo'q | 2 |
| city_id | yo'q | 25 |
| district | yo'q | Qorovulbozor |
| contact_name | yo'q | Akbar aka |
| delivery_available | yo'q | true yoki 1 |
| quantity | yo'q | 10 |
| unit | yo'q | kg |
| media[] | yo'q | rasm/video fayllar (bir nechta) |

**Muvaffaqiyat (201):** `status: "pending"` — hali public ro'yxatda ko'rinmaydi.

**422 xatolar:**

| Xabar | Sabab |
|-------|-------|
| E'lon faqat eng oxirgi (ichki) subkategoriyada... | `subcategory_id` ota (bolalari bor) |
| Subkategoriya tanlangan kategoriyaga tegishli emas... | `category_id` bilan `subcategory_id` mos emas |
| Kategoriya topilmadi yoki faol emas | `is_active=false` yoki noto'g'ri ID |

---

## 4. Admin: tasdiqlash

`GET /admin/ads?status=pending` — Bearer (admin token)

`PATCH /admin/ads/{id}/approve`

Keyin e'lon `status: active` bo'ladi.

---

## 5. Ochiq ro'yxatda tekshirish

`GET /public/ads` — faqat `active` e'lonlar

`GET /public/ads?category_id=1&subcategory_id=2`

`GET /public/ads/{id}` — bitta kartochka

---

## 6. Sotuvchi: o'z e'lonlari

`GET /profile/ads` — barcha statuslar (pending, active, ...)

`GET /profile/ads/{id}`

`POST /profile/ads/{id}` — yangilash (form-data + media)

`DELETE /profile/ads/{id}`

---

## 7. Promo (ixtiyoriy, faqat active e'lon)

`GET /resources/promotion-plans`

`POST /profile/ads/{ad}/promotions` — `promotion_plan_id`

Admin: `PATCH /admin/ad-promotions/{id}/confirm`

---

## Tez test ketma-ketligi

1. Admin login → category → root subcategory → child subcategory (leaf)
2. `GET /resources/subcategories?category_id=1` → `parent_id=...` → leaf `id` ni yozib oling
3. Seller login → `POST /profile/ads` (leaf `subcategory_id` bilan)
4. Admin `PATCH /admin/ads/{id}/approve`
5. `GET /public/ads` — e'lon chiqishi kerak
