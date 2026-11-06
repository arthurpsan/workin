# workIn

Simple task manager built with plain PHP, MySQL and RedBeanPHP. No frameworks, only HTML, CSS and POST forms.

## Features

- Kanban board with New, Active and Closed columns
- Filter and sort by priority and deadline
- Deadlines, multiple assignees and per-task checklists
- Home summary with upcoming deadlines
- Trash bin (restore or delete permanently)
- Team page and profile page with photo upload
- Responsive layout (desktop, tablet, mobile)

## Setup

1. Copy the project to your web root (e.g. `htdocs/workin`).
2. Start Apache and MySQL (XAMPP works fine).
3. Open `http://localhost/workin/index.php`.

The database, tables and a default user are created automatically.

**Default login:** `admin@workin.com` / `123456`
You will be asked to change both on first access.

If your MySQL has a password, edit the variables at the top of `config.php`.

## Structure

| File                 | Purpose                                         |
| -------------------- | ----------------------------------------------- |
| `index.php`          | Login                                           |
| `landing.php`        | Home with status summary and upcoming deadlines |
| `tarefas.php`        | Kanban board with filters                       |
| `tarefa_ver.php`     | Task details and checklist                      |
| `tarefa_nova.php`    | Create and edit tasks                           |
| `tarefa_excluir.php` | Delete (sends to trash)                         |
| `lixeira.php`        | Trash bin                                       |
| `equipe.php`         | Team members                                    |
| `perfil.php`         | Profile and photo                               |
| `config.php`         | Database, session and layout helpers            |
| `estilo.css`         | Styles                                          |

## Rules

- Closed tasks stay visible for 7 days, then move to the trash automatically (`$DIAS_FECHADA` in `config.php`).
- New team members must set their own password on first login.
- Profile photos are saved in `fotos/` (JPG, PNG, GIF or WEBP, up to 2 MB).

## Stack

PHP, MySQL, RedBeanPHP (`rb-mysql.php`, included), HTML and CSS.
Tested on PHP 8.3 with MariaDB 10.11.
