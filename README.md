# CodeForge

CodeForge is a Mini GitHub-style platform built on top of the existing Laravel + React/Inertia application in this repository. It lets authenticated users create repositories, receive an SSH clone URL, inspect commits and files, and track simple issues.

## Current architecture

This project keeps the existing monolithic structure instead of forcing a risky split into `backend/` and `frontend/`:

```txt
CodeForge/
├── app/
├── bootstrap/
├── config/
├── database/
├── repositories/
├── resources/
│   └── js/
│       └── pages/codeforge/
├── routes/
├── storage/
└── ...
```

- Backend: Laravel 13
- Frontend: React + Inertia + TypeScript
- Database: PostgreSQL supported through Laravel's `pgsql` connection
- Git integration: bare repositories created on the Linux server through `GitService`

## Features implemented

- User registration and login through Laravel/Fortify plus JSON API auth endpoints
- Repository CRUD endpoints
- Bare Git repository creation on the server
- SSH clone URL generation
- Repository files, commits, and branches API endpoints
- Basic issue tracking
- Simple repository visibility and membership checks
- CodeForge pages in the existing React/Inertia UI

## Environment

Copy `.env.example` to `.env` if you do not already have one and review the values before running migrations.

Recommended PostgreSQL-related settings:

```env
APP_NAME=CodeForge
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=codeforge
DB_USERNAME=codeforge
DB_PASSWORD=codeforge_password

CODEFORGE_REPOSITORIES_ROOT=/home/robison/projects/CodeForge/repositories
CODEFORGE_SSH_HOST=172.21.227.83
CODEFORGE_SSH_PORT=2230
CODEFORGE_SSH_USER=robison
CODEFORGE_AUTHORIZED_KEYS_PATH=/home/robison/.ssh/authorized_keys
```

Important:

- Do not overwrite an existing production `.env` blindly.
- The server currently needs `node` and `npm` installed before frontend assets can be built there.

## Install and run

### Backend

```bash
cd /home/robison/projects/CodeForge
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend

```bash
cd /home/robison/projects/CodeForge
npm install
npm run dev
```

Because the project is monolithic, both Composer and NPM run from the repository root.

## API endpoints

### Auth

```txt
POST /api/register
POST /api/login
POST /api/logout
GET  /api/user
```

### Repositories

```txt
GET    /api/repositories
POST   /api/repositories
GET    /api/repositories/{id}
DELETE /api/repositories/{id}
GET    /api/repositories/{id}/files
GET    /api/repositories/{id}/commits
GET    /api/repositories/{id}/branches
```

### Issues

```txt
GET    /api/repositories/{id}/issues
POST   /api/repositories/{id}/issues
GET    /api/issues/{id}
PUT    /api/issues/{id}
DELETE /api/issues/{id}
```

## Git workflow

Once you create a repository from the web UI, CodeForge creates a bare Git repository under `repositories/<owner>/<repo>.git`.

Example clone command:

```bash
git clone ssh://robison@172.21.227.83:2230/home/robison/projects/CodeForge/repositories/<owner>/<repo>.git
```

The repository detail page now also includes:

- An SSH setup guide with copy/paste commands
- A user SSH keys screen at `/codeforge/ssh-keys`
- An HTTP clone slot that stays disabled until a real Git HTTP backend is configured on the server

Example first push:

```bash
cd <repo>
echo "# Demo" > README.md
git add .
git commit -m "Initial commit"
git branch -M main
git push origin main
```

## Main files added

- `app/Services/GitService.php`
- `app/Http/Controllers/Api/*`
- `app/Http/Controllers/CodeForge/PageController.php`
- `app/Models/Repository.php`
- `app/Models/Issue.php`
- `app/Models/RepositoryMember.php`
- `database/migrations/2026_06_07_*`
- `resources/js/pages/codeforge/*`
- `config/codeforge.php`

## Notes

- This implementation adapts to the existing Laravel + Inertia structure instead of replacing it with separate `backend/` and `frontend/` folders.
- If you want a true split architecture later, we can do that as a second-phase refactor once this version is stable.
