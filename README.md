# AI Soru Cevap Yarismasi

Turkish AI-powered quiz application using Google Gemini API for dynamic question generation with comprehensive social features, achievements, and user management system.

## Features

### Core Game System
- **Dynamic Question Generation**: Google Gemini API integration with database caching for performance
- **Multiple Categories**: Dynamic categories (History, Sports, Science, Arts, Geography, etc.)
- **Difficulty Levels**: Easy, Medium, Hard with adaptive scoring
- **Lifeline System**: 50/50, Extra Time, Pass options with in-game currency

### Social Features
- **Friends System**: Add friends, send/receive requests, view friend performance
- **Duel System**: Challenge friends to 1v1 quiz battles with custom question counts (5-25)
- **Leaderboards**: Global and category-specific rankings with performance tracking

### User Management
- **Role-based Access**: User and Admin roles with different permissions
- **Achievement System**: 20+ dynamic achievements with automatic tracking
- **Daily Quests**: Rotating daily challenges with rewards
- **Login Streaks**: Daily login rewards to encourage engagement

### Economy System
- **Token Currency**: Earn tokens from correct answers, quests, and duels
- **In-game Shop**: Purchase lifelines and upgrades with earned tokens
- **Dynamic Pricing**: Admin-configurable shop prices with sales analytics

### Admin Panel
- **User Management**: View, edit, and manage user accounts
- **Analytics Dashboard**: Comprehensive statistics with Chart.js visualizations
- **Announcement System**: Send notifications to users
- **Shop Management**: Configure prices and view sales data
- **Category Management**: Add/edit quiz categories dynamically

## Technology Stack

- **Backend**: PHP 7.4+ with MVC architecture
- **Database**: MySQL with comprehensive migration system
- **Frontend**: Vanilla JavaScript with modular design
- **UI Framework**: Tailwind CSS for responsive design
- **AI Integration**: Google Gemini API for question generation
- **Authentication**: PHP Sessions with CSRF protection

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Google Gemini API key

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ai-soru-cevap
   ```

2. **Create configuration file**
   ```php
   // config.php
   <?php
   // Database Settings
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'ai_quiz');

   // Google Gemini API
   define('GEMINI_API_KEY', 'your_gemini_api_key');

   // Domain Configuration
   define('DOMAIN', 'https://your-domain.com');
   ?>
   ```

3. **Run database installation**
   ```bash
   # For development (WARNING: Deletes all data)
   http://localhost/ai-soru-cevap/install.php?fresh=1

   # For production (Safe update)
   http://localhost/ai-soru-cevap/install.php
   ```

4. **Access the application**
   - URL: `http://localhost/ai-soru-cevap/`
   - Default admin: username `admin`, password `password`

## Database Schema

Current schema version: **1.10.0**

### Core Tables
- `users` - User accounts with coins, lifelines, login streaks
- `categories` - Dynamic question categories with icons/colors
- `questions` - Cached AI-generated questions for performance
- `leaderboard` - Global scoring system
- `user_stats` - Per-category performance tracking

### Social System
- `friends` - Friend relationships with status tracking
- `duels` - 1v1 challenge system with questions and scores
- `achievements` / `user_achievements` - Badge system
- `achievement_rules` - Dynamic achievement tracking rules

### Quest & Economy
- `quests` / `user_quests` - Daily task system
- `quest_history` - Quest completion analytics
- `shop_settings` - Dynamic pricing configuration
- `purchase_logs` - Transaction history

### Admin System
- `announcements` / `user_announcements` - Notification system
- `settings` - Site configuration
- `api_keys` - Multiple Gemini API key support
- `schema_migrations` - Database version tracking

## Architecture

### Backend (MVC Pattern)
- **API Router**: `api.php` - Central request handler with CSRF protection
- **Controllers**: `Api/Controllers/` - Business logic separation
  - `GameController` - Question generation and answer validation
  - `UserController` - Authentication and user management
  - `DuelController` - Challenge system
  - `AdminController` - Admin panel functionality
  - `DataController` - Statistics and leaderboards

### Frontend (Modular MPA)
- **Multi-Page Application**: Independent PHP pages with shared components
- **Modular JavaScript**: Feature-specific handlers in `assets/js/`
- **State Management**: Global state through `app-state.js`
- **API Communication**: Centralized through `api-handler.js`

### Performance Optimizations
- **Question Caching**: Database-first approach reduces AI API calls by ~90%
- **Asset Cache Busting**: Timestamp-based versioning
- **Optimized Queries**: Proper indexing and query optimization
- **Modular Loading**: JavaScript modules loaded per page

## Migration System

The application uses a robust migration system for database updates:

- **Version Tracking**: `schema_migrations` table tracks applied migrations
- **Safe Updates**: Production updates preserve existing data
- **Rollback Support**: Transaction-based migrations with automatic rollback
- **Development Reset**: Fresh install option for development environments

### Migration Commands
```bash
# Safe production update
php install.php

# Development reset (destroys data)
php install.php?fresh=1
```

## Development

### Adding New Features
1. Create controller method in appropriate `Api/Controllers/` file
2. Add route to `api.php` routes array
3. Create corresponding handler in `assets/js/`
4. Update UI templates in appropriate PHP page files

### Database Changes
1. Increment `$current_version` in `install.php`
2. Create new `migration_X_X_X($pdo)` function
3. Use transactions for data safety
4. Add case to migration switch statement

### Code Organization
- Controllers handle business logic and database operations
- Handlers manage UI interactions and API communication
- Modular JavaScript prevents monolithic client-side code
- Consistent error handling and logging patterns

## Security Features

- **Authentication**: Secure password hashing with PHP `password_hash()`
- **CSRF Protection**: Token validation on all authenticated requests
- **SQL Injection Prevention**: PDO prepared statements throughout
- **Rate Limiting**: Failed login attempt tracking
- **Session Management**: Secure PHP session handling
- **Input Validation**: Comprehensive server-side validation

## API Key Management

The application supports multiple Google Gemini API keys for reliability:
- Database-stored API keys with usage tracking
- Automatic failover between keys
- Admin panel for key management
- Usage analytics and monitoring

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow existing code patterns and conventions
4. Test thoroughly including migration scenarios
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and documentation, please refer to:
- `CLAUDE.md` - Comprehensive development guide
- Migration logs in `schema_migrations` table
- Error logs in server error logs
- Admin panel analytics for system health