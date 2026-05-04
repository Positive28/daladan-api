# Rasm Conversions — Serverga qo'yish bo'yicha tayyor qo'llanma

Bu hujjat `thumb / medium / large / xlarge` conversions ishlashi uchun production serverda bajariladigan amaliy qadamlarni beradi.

---

## 1) Serverga ulanish

```bash
ssh root@YOUR_SERVER_IP
cd /var/www/daladan-api
```

`YOUR_SERVER_IP` o'rniga server IP kiriting.

---

## 2) Kodni yangilash

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
```

---

## 3) Queue sozlamasini tekshirish

`.env` faylda:

```env
QUEUE_CONNECTION=database
```

`sync` bo'lmasin, aks holda production uchun noqulay bo'ladi.

Sozlama o'zgarsa:

```bash
php artisan config:clear
```

---

## 4) Queue jadvali (agar kerak bo'lsa)

Agar `jobs` jadvali hali yaratilmagan bo'lsa:

```bash
php artisan queue:table
php artisan migrate --force
```

> Agar oldin yaratilgan bo'lsa, bu qadamni o'tkazib yuboring.

---

## 5) Supervisor o'rnatish (yo'q bo'lsa)

```bash
apt update
apt install -y supervisor
```

---

## 6) Queue worker uchun supervisor config

Yangi fayl yarating:

```bash
nano /etc/supervisor/conf.d/start-api-worker.conf
```

Ichiga quyidagini qo'ying:

```ini
[program:start-api-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/daladan-api/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/daladan-api/storage/logs/worker.log
stopwaitsecs=3600
```

Saqlang va chiqing.

---

## 7) Supervisorni ishga tushirish

```bash
supervisorctl reread
supervisorctl update
supervisorctl start start-api-worker:*
supervisorctl status
```

Holat `RUNNING` bo'lishi kerak.

---

## 8) Oldingi rasmlar uchun conversions yaratish

Bu buyruqni bir marta ishga tushiring:

```bash
php artisan media-library:regenerate
```

Rasmlar ko'p bo'lsa vaqt oladi.

---

## 9) Tekshiruv

Worker logini ko'rish:

```bash
tail -f /var/www/daladan-api/storage/logs/worker.log
```

API javobida `media_list` ichida quyidagi maydonlar borligini tekshiring:

- `thumb_url`
- `medium_url`
- `large_url`
- `xlarge_url`
- `original_url`

---

## 10) Deploydan keyingi qisqa checklist

Har deploydan keyin odatda:

```bash
cd /var/www/daladan-api
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
supervisorctl restart start-api-worker:*
```

`media-library:regenerate` esa faqat yangi conversion sxemasi qo'shilganda yoki eski fayllarni qayta tayyorlash kerak bo'lganda bajariladi.

