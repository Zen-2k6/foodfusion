# FoodFusion

FoodFusion is a PHP, MySQL and JavaScript student project for an NCC Level 5 Back End Web Development assignment. It is designed for a local XAMPP classroom demonstration, not production deployment.

## Database setup in XAMPP

1. Open XAMPP and start **Apache** and **MySQL**.
2. Visit [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Select **Import**.
4. Choose `database/foodfusion.sql` from this project.
5. Select **Import** or **Go** at the bottom of the page.
6. Check that the `foodfusion` database contains eleven tables.
7. Visit [http://localhost/foodfusion/](http://localhost/foodfusion/).

The project includes a simple PDO connection file at `config/database.php`. Its settings match the normal local XAMPP defaults:

- Host: `localhost`
- Database: `foodfusion`
- Username: `root`
- Password: empty

If your local MySQL password is different, change only `$password` in `config/database.php`.

## Demo accounts

The SQL file includes two sample accounts. Their stored passwords are encrypted with PHP's `password_hash()` function.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@foodfusion.test` | `Admin123!` |
| Member | `member@foodfusion.test` | `Member123!` |

These accounts are only for local classroom testing.

## Included pages and functions

- Responsive homepage with mission statement, news feed, featured recipes and event carousel
- Join Us pop-up and separate accessible registration page
- Secure registration using `password_hash()`
- Login using `password_verify()`
- Three-attempt account lockout that automatically ends after three minutes
- Localhost-friendly password reset with a random, ten-minute session token
- Member profile editing and password changing
- Two user roles: member and admin
- Protected admin dashboard with summary counts, community moderation, recipe status controls, recent users and contact messages
- About Us, values and team content
- Public recipe collection with keyword search plus cuisine, diet and difficulty filters
- Recipe details, comments, likes and saved recipes
- Community cookbook with recipes, tips and experiences
- Community feed showing only admin-approved member posts
- Login-protected My Wall with owner-only create, edit and delete controls
- Pending, Approved and Rejected moderation statuses
- Likes and comments for approved community posts
- Recipe-submission and interaction JSON web services
- Contact form saved to MySQL
- Downloadable PDF recipe cards, kitchen guides and renewable-energy resources
- External cooking tutorials, instructional videos and educational articles
- Privacy policy, cookie information and cookie acceptance banner
- Facebook, Instagram, YouTube and TikTok links

## Main folders

| Path | Purpose |
|---|---|
| `database/` | MySQL database structure and sample records |
| `config/` | Shared application and database settings |
| `includes/` | Reusable header, footer and simple PHP functions |
| `api/` | PHP JSON services for recipe submission and interactions |
| `assets/css/` | Responsive visual design |
| `assets/js/` | Menu, modal, carousel, cookie and API behaviour |
| `output/pdf/` | Downloadable recipe cards, guides and infographics |

## Security features

- Passwords are hashed and are never stored as plain text.
- PDO prepared statements protect database queries from SQL injection.
- CSRF tokens protect forms from unwanted submissions.
- `htmlspecialchars()` safely displays user-created content.
- Session IDs are renewed after login and logout.
- Login attempts are counted in MySQL and accounts are locked for three minutes after the third failure.
- Submitted image links must be valid HTTPS URLs.

The security is suitable for demonstrating the assignment requirements locally. A production system would also need HTTPS configuration, email-based password recovery, server-side logging and stronger content moderation.

## ERD mapping

The screenshot uses some singular names such as `User`, `Recipe` and `comment`. The database uses plural table names consistently (`users`, `recipes`, and `comments`) because they are clearer in SQL and avoid confusion with MySQL's `USER()` function.

All nine original ERD entities are included:

- `users`
- `recipes`
- `comments`
- `interactions`
- `community_posts`
- `news_posts`
- `events`
- `contact_messages`
- `resources`

Two supporting tables were added for the complete Community Cookbook feature:

- `community_post_likes`
- `community_post_comments`

Foreign keys use `ON DELETE CASCADE` when a child record should not exist without its parent, or `ON DELETE SET NULL` when public content should remain after its author account is removed.

The `failed_attempts` and `locked_until` fields support the assignment's three-attempt, three-minute account lockout implemented in `login.php`.

## Online images

No images were generated or saved in this project. Sample records contain external Unsplash image URLs. The URLs and source notes are listed in `IMAGE_SOURCES.md`.
