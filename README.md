# PHP Responsive Login System (MySQLi + Bootstrap 5)

ระบบ Login & Register แบบ Responsive ด้วย **PHP (MySQLi)** + **Bootstrap 5**  
รองรับ Session, Password Hashing, Login ผ่าน Username หรือ Email, และระบบ Reports Export CSV

> ทำเพื่อใช้งานจริง + งานโปรเจกต์ มุ่งเน้นความเรียบง่ายแต่ปลอดภัยในระดับพื้นฐาน

---

## 📂 ไฟล์ในโปรเจกต์

| ไฟล์                         | หน้าที่                                      |
| ---------------------------- | -------------------------------------------- |
| `config_mysqli.php`          | ตั้งค่าการเชื่อมต่อฐานข้อมูล + start session |
| `register.php`               | ลงทะเบียนผู้ใช้ใหม่                          |
| `login.php`                  | หน้าแบบฟอร์มเข้าสู่ระบบ (responsive)         |
| `logout.php`                 | ออกจากระบบ                                   |
| `dashboard.php`              | หน้าหลังเข้าสู่ระบบ (ต้อง login)             |
| `reports.php`                | เลือกช่วงวัน + ดูรายงาน                      |
| `export.php`                 | Export ข้อมูลเป็น CSV                        |
| `users.sql`                  | โครงสร้างตาราง Users (MySQL)                 |
| `csrf.php` _(ตัวเลือก)_      | ฟังก์ชัน CSRF token (เพิ่มได้ภายหลัง)        |
| `make_user.php` _(optional)_ | แทรก user ทดสอบแบบ hash                      |

---

## 🧠 ฟีเจอร์ที่รองรับ

| ฟีเจอร์ | สถานะ |
| ------- | ----- |

✅ Register / Login / Logout  
✅ Login ได้ทั้ง Email หรือ Username  
✅ Password Hash (bcrypt)  
✅ Session + Auth Guard  
✅ Dashboard UI (Bootstrap)  
✅ Reports + Export CSV  
✅ ป้องกัน SQL Injection (Prepared Statements)  
✅ โครงสร้างไฟล์แยกเพื่อดูแลง่าย

> ใช้ **mysqli + prepared statements** เพื่อความปลอดภัยพื้นฐาน

---

## 🛠 วิธีใช้งาน

### 1) สร้างฐานข้อมูล

สร้าง Database เช่น `myapp` หรือ `login_system`

นำไฟล์ `users.sql` ไปรันใน phpMyAdmin / MySQL CLI เพื่อสร้างตาราง

### 2) ตั้งค่า config

แก้ไขไฟล์ `config_mysqli.php`

```php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "myapp";
```
