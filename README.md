# Knowledge Base App

A Laravel 13 + Vue 3 application for learning with interview questions and answers. Features full-text search, progress tracking, and bookmarks.

## Features

- **Full-text search** - Search across all questions and answers
- **Progress tracking** - Mark questions as completed with checkboxes
- **Bookmarks** - Save favorite questions (requires authentication)
- **Markdown rendering** - Code syntax highlighting support
- **Topic navigation** - Browse by topic (PHP, Laravel, Vue, Database, etc.)
- **Responsive design** - Works on desktop and mobile

## Tech Stack

- Laravel 13 (PHP 8.4)
- Vue 3 with Inertia
- MySQL 8
- Tailwind CSS
- Docker

## Quick Start

```bash
# Start the application
cd knowledge-base
docker-compose up -d

# Access the app
open http://localhost:8007
```

## Project Structure

```
knowledge-base/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Http/
│   │   ├── Controllers/        # API controllers
│   │   ├── Middleware/         # Custom middleware
│   │   └── Requests/          # Form requests
│   ├── Models/                # Eloquent models
│   ├── Policies/              # Authorization policies
│   ├── Providers/             # Service providers
│   ├── Repositories/          # Data access layer
│   └── Services/              # Business logic
├── resources/
│   ├── js/
│   │   ├── Components/         # Vue components
│   │   ├── Layouts/           # App layouts
│   │   └── Pages/             # Inertia pages
│   └── markdown/              # Question content (md files)
├── database/
│   └── migrations/            # Database migrations
└── docker-compose.yml          # Docker services
```

## Managing Content

Questions are stored as markdown files in `resources/markdown/`:

```
resources/markdown/
├── PHP/
│   ├── fundamentals.md
│   └── oop.md
├── Laravel/
├── Database/
└── ...
```

### Reload content

After adding/editing markdown files:

```bash
docker exec kb_app php artisan markdown:load
```

### Markdown format

Each question should follow this format:

```markdown
## Question 1: Your Question Title

**Answer:**

Your answer content here...

### Sub-sections as needed

**Follow-up:**
- Related questions to know

**Key Points:**
- Summary of critical takeaways

---

## Question 2: Next Question
```

### Adding New Questions

1. **Analyze** - Determine the appropriate category (PHP, Laravel, Vue, Database, System-Design, etc.)
2. **Search** - Check existing files for related content before creating new files
3. **Follow format** - Use the structure shown above
4. **Run reload** - Execute `php artisan markdown:load` after adding content

## Database

### Migrations

```bash
docker exec kb_app php artisan migrate
```

### Seed data

```bash
docker exec kb_app php artisan markdown:load
```

## Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker logs kb_app

# Execute commands in container
docker exec -it kb_app php artisan <command>
docker exec -it kb_app bash

# Rebuild after changes
docker-compose build app
docker-compose up -d
```

## Environment

Default configuration (see `docker-compose.yml`):

| Service | Internal Port | External Port |
|---------|--------------|---------------|
| App    | 8000         | 8007          |
| MySQL  | 3306         | 33068         |

### Environment Variables

```
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=knowledge_base
DB_USERNAME=kb_user
DB_PASSWORD=kb_pass
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | / | Home page |
| GET | /topic/{slug} | Topic questions |
| GET | /question/{slug} | Question detail |
| GET | /search?q=query | Search questions |
| POST | /progress/toggle | Toggle completion (auth) |
| POST | /bookmark/toggle | Toggle bookmark (auth) |

## Authentication

The app uses Laravel Breeze for authentication. Users can:

- Register/Login
- Track progress (saved to database)
- Bookmark questions

## Search

Search uses MySQL LIKE queries to find questions by title and content. Results include topic name and excerpt.

## Development

### Running tests

```bash
docker exec kb_app php artisan test
```

### Clear cache

```bash
docker exec kb_app php artisan config:clear
docker exec kb_app php artisan cache:clear
docker exec kb_app php artisan view:clear
```

## Troubleshooting

### Container won't start

```bash
# Check logs
docker logs kb_app

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Database connection issues

```bash
# Check MySQL is running
docker ps

# Test connection
docker exec kb_app php -r "new PDO('mysql:host=mysql', 'kb_user', 'kb_pass');"
```

### Port conflicts

If ports 8007 or 33068 are in use, edit `docker-compose.yml` to change them.

## License

MIT License
