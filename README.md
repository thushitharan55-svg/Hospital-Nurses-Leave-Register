# Hospital Nurses Leave Register System

PHP 8+ and MySQL application for nurse registration, personal profiles, leave applications (annual, casual, medical), matron approval, leave balances, and an admin dashboard.

## Requirements

- XAMPP (Apache + PHP 8.0 or newer + MySQL/MariaDB)
- Browser with JavaScript enabled

## Folder layout

```
NursesLeaveSystem/
  admin/            Admin dashboards and management pages
  assets/           CSS and JavaScript
  auth/             Login, registration, logout
  config/           App and database settings
  database/         schema.sql and seed.sql
  includes/         Auth, leave logic, layout
  matron/           Matron review workspace
  nurse/            Nurse portal
  index.php         Public home
```

## XAMPP setup

1. Copy this project into `C:\xampp\htdocs\NursesLeaveSystem` (or keep it where it is and add an Apache alias).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Import **in this order**:
   - `database/schema.sql` (creates `nurses_leave_system` and tables)
   - `database/seed.sql` (demo users and sample leave data)
5. Default database login in `config/database.php` is:
   - Host: `127.0.0.1`
   - Database: `nurses_leave_system`
   - User: `root`
   - Password: empty (XAMPP default)

   Change `DB_PASS` if you set a MySQL root password.
6. Open [http://localhost/NursesLeaveSystem/](http://localhost/NursesLeaveSystem/)

If the project is not in `htdocs`, copy it there, or configure a virtual host whose document root is this folder.

## Demo accounts

All seeded passwords are `password`.

| Role   | Email                 | Notes                          |
|--------|-----------------------|--------------------------------|
| Admin  | admin@hospital.local  | Full management                |
| Matron | matron@hospital.local | Approve / reject leave         |
| Nurse  | nurse@hospital.local  | Sample balance and applications|
| Nurse  | anura@hospital.local  | Second staff nurse             |

Register additional nurses from the public **Nurse registration** page. New accounts receive a generated employee number (`NLS-YYYY-####`) and a current-year leave balance.

## Roles

- **Nurse:** register, sign in, edit only their own profile, apply for leave, view balances, cancel pending applications.
- **Matron:** view staff, review applications, approve (deducts days) or reject.
- **Admin:** manage nurses, matrons, user accounts, leave records, and yearly balances.

## Leave rules

Yearly entitlements (configurable in `config/config.php`):

- Annual: 21 days
- Casual: 7 days
- Medical: 14 days

Inclusive calendar days are counted. Overlapping pending or approved leave is blocked. Approval is transactional and updates `leave_balances`.

## Security

- Passwords stored with `password_hash()` / verified with `password_verify()`
- Session login with regeneration on sign-in
- Role checks on every private page
- CSRF tokens on POST forms
- PDO prepared statements
- Nurses can update only the profile row matching their `user_id`

## Changing entitlements

Edit `LEAVE_ENTITLEMENTS` in `config/config.php`. Existing yearly rows are not rewritten automatically; adjust them in **Admin → Leave balances**.
