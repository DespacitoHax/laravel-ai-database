# Laravel AI Database Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mostafaarafat/laravel-ai-database.svg?style=flat-square)](https://packagist.org/packages/mostafaarafat/laravel-ai-database)
[![Total Downloads](https://img.shields.io/packagist/dt/mostafaarafat/laravel-ai-database.svg?style=flat-square)](https://packagist.org/packages/mostafaarafat/laravel-ai-database)
[![License](https://img.shields.io/packagist/l/mostafaarafat/laravel-ai-database.svg?style=flat-square)](https://packagist.org/packages/mostafaarafat/laravel-ai-database)

Transform natural language questions into SQL queries and get human-readable answers using AI. This Laravel package integrates multiple AI providers (Anthropic Claude, OpenAI, Google Gemini) to make database querying accessible to everyone.

## ✨ Features

- 🤖 **Multiple AI Providers**: Support for Anthropic Claude, OpenAI GPT, and Google Gemini
- 🔒 **Security First**: Built-in strict mode prevents write operations
- ⚡ **Smart Schema Detection**: Automatically identifies relevant tables for queries
- 💾 **Schema Caching**: Reduces API calls and improves performance
- 🎯 **Multiple Interfaces**: Use via Facade, DB helper, or Artisan commands
- 🗃️ **Multi-Database Support**: Works with MySQL, PostgreSQL, and SQLite
- 📊 **Flexible Responses**: Get SQL queries, human answers, or detailed results

## 📋 Requirements

- PHP 8.0 or higher
- Laravel 8.x, 9.x, 10.x, or 11.x (via illuminate/support)
- One of the following AI provider API keys:
  - Anthropic API key (recommended)
  - OpenAI API key
  - Google Gemini API key

## 📦 Installation

### Step 1: Install via Composer
```bash
composer require mostafaarafat/laravel-ai-database
```

### Step 2: Publish Configuration
```bash
php artisan vendor:publish --tag=ai-database-config
```

This creates `config/ai-database.php` in your Laravel application.

### Step 3: Configure Environment Variables

Add your AI provider credentials to `.env`:
```env
# Choose your AI provider (anthropic, openai, or gemini)
AI_DATABASE_PROVIDER=anthropic

# Anthropic Configuration
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxx
ANTHROPIC_MODEL=claude-sonnet-4-20250514

# OpenAI Configuration (alternative)
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4o-mini

# Google Gemini Configuration (alternative)
GEMINI_API_KEY=xxxxxxxxxxxxxxxxxxxxx
GEMINI_MODEL=gemini-1.5-pro

# Database Configuration
AI_DATABASE_CONNECTION=mysql
AI_DATABASE_STRICT_MODE=true
AI_DATABASE_MAX_TABLES=15

# Cache Configuration
AI_DATABASE_CACHE_ENABLED=true
AI_DATABASE_CACHE_TTL=3600
```

## 🚀 Quick Start

### Basic Usage
```php
use Illuminate\Support\Facades\DB;

// Get a human-readable answer
$answer = DB::ask('How many users are registered?');
// Output: "There are 1,234 users registered in the database."

// Get just the SQL query
$sql = DB::askForQuery('Show me users created this month');
// Output: "SELECT * FROM users WHERE created_at >= '2025-02-01'"

// Get detailed response with all information
$result = DB::askDetailed('What is the total revenue?');
// Output: [
//     'question' => 'What is the total revenue?',
//     'sql' => 'SELECT SUM(amount) as total FROM orders',
//     'results' => [['total' => 50000]],
//     'answer' => 'The total revenue is $50,000.',
//     'rows' => 1
// ]
```

### Using Artisan Commands
```bash
# Get a natural language answer
php artisan db:ask "How many active users do we have?"

# Show detailed information including SQL and raw results
php artisan db:ask "Show me top 5 products" -v

# Get only the SQL query
php artisan db:ask "List all pending orders" --sql-only

# Use a specific database connection
php artisan db:ask "How many records?" --connection=secondary
```

## 📚 Detailed Usage

### 1. Getting Human-Readable Answers

The `ask()` method generates SQL, executes it, and returns a natural language answer:
```php
use Illuminate\Support\Facades\DB;

// Simple questions
$answer = DB::ask('How many users are there?');

// Complex aggregations
$answer = DB::ask('What is the average order value for the last 30 days?');

// Filtering and sorting
$answer = DB::ask('Show me the top 5 customers by total spending');

// Joins and relationships
$answer = DB::ask('Which products have never been ordered?');
```

### 2. Getting SQL Queries Only

Use `askForQuery()` when you want the SQL without executing it:
```php
// Get the SQL query
$sql = DB::askForQuery('Find users who signed up today');

// You can then modify or log it
Log::info('Generated SQL:', ['sql' => $sql]);

// Execute it yourself if needed
$results = DB::select($sql);
```

### 3. Getting Detailed Results

Use `askDetailed()` for complete information:
```php
$result = DB::askDetailed('Show me recent orders');

// Access all parts of the response
echo $result['question'];  // The original question
echo $result['sql'];       // The generated SQL
print_r($result['results']); // Raw database results
echo $result['answer'];    // Human-readable answer
echo $result['rows'];      // Number of rows returned
```

### 4. Using Specific Database Connections
```php
// Use a specific connection
$answer = DB::ask('How many records?', 'secondary');

$sql = DB::askForQuery('List all items', 'analytics');

$result = DB::askDetailed('Show statistics', 'reporting');
```

### 5. Using in Controllers
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function query(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500'
        ]);

        try {
            $result = DB::askDetailed($request->question);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### 6. Using in Blade Views
```php
// In your controller
public function dashboard()
{
    $userCount = DB::ask('How many active users?');
    $revenue = DB::ask('What is total revenue this month?');
    
    return view('dashboard', compact('userCount', 'revenue'));
}
```
```blade
<!-- In your blade view -->
<div class="stats">
    <div class="stat">
        <h3>Active Users</h3>
        <p>{{ $userCount }}</p>
    </div>
    
    <div class="stat">
        <h3>Monthly Revenue</h3>
        <p>{{ $revenue }}</p>
    </div>
</div>
```

## ⚙️ Configuration

### AI Providers

The package supports three AI providers. Configure them in `config/ai-database.php`:
```php
'providers' => [
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        'max_tokens' => 2048,
        'temperature' => 0.1,
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => 2048,
        'temperature' => 0.1,
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'temperature' => 0.1,
    ],
],
```

**Choosing a Provider:**

- **Anthropic Claude** (Recommended): Best for SQL generation, strong reasoning
- **OpenAI GPT**: Popular, well-tested, good performance
- **Google Gemini**: Free tier available, fast responses

### Database Connection

Specify which database connection to use:
```php
'connection' => env('AI_DATABASE_CONNECTION', null),
```

Leave as `null` to use your default connection, or specify a connection name from `config/database.php`.

### Strict Mode

Strict mode prevents dangerous SQL operations:
```php
'strict_mode' => env('AI_DATABASE_STRICT_MODE', true),
```

When enabled:
- ✅ Allows: `SELECT` queries only
- ❌ Blocks: `INSERT`, `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, `ALTER`, etc.

**Disable only if you trust the AI completely and understand the risks.**

### Table Limit

When your database has many tables, the package uses AI to identify relevant ones:
```php
'max_tables' => env('AI_DATABASE_MAX_TABLES', 15),
```

- If you have ≤ 15 tables: All table schemas sent to AI
- If you have > 15 tables: AI first identifies relevant tables, then gets their schemas

### Caching

Enable caching to reduce API calls:
```php
'cache' => [
    'enabled' => env('AI_DATABASE_CACHE_ENABLED', true),
    'ttl' => env('AI_DATABASE_CACHE_TTL', 3600), // 1 hour
    'prefix' => 'ai_database',
],
```

Database schemas are cached for the specified TTL (Time To Live) in seconds.

## 🔧 Advanced Usage

### Error Handling
```php
use Illuminate\Support\Facades\DB;

try {
    $answer = DB::ask('Your question here');
    echo $answer;
} catch (\Exception $e) {
    // Handle errors
    Log::error('AI Database Error:', [
        'message' => $e->getMessage(),
        'question' => 'Your question here'
    ]);
    
    // Show user-friendly message
    return response()->json([
        'error' => 'Unable to process your question. Please try again.'
    ], 500);
}
```

### Logging Queries
```php
$result = DB::askDetailed('How many orders today?');

// Log the generated SQL
Log::info('AI Generated Query', [
    'question' => $result['question'],
    'sql' => $result['sql'],
    'execution_time' => microtime(true) - $start
]);
```

### Building a Query Interface
```php
// routes/web.php
Route::get('/ai-query', [QueryController::class, 'index']);
Route::post('/ai-query', [QueryController::class, 'execute']);

// app/Http/Controllers/QueryController.php
class QueryController extends Controller
{
    public function index()
    {
        return view('ai-query');
    }
    
    public function execute(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500'
        ]);
        
        $result = DB::askDetailed($request->question);
        
        return view('ai-query', compact('result'));
    }
}
```
```blade
<!-- resources/views/ai-query.blade.php -->
<form method="POST" action="/ai-query">
    @csrf
    <input type="text" name="question" placeholder="Ask a question about your database">
    <button type="submit">Ask AI</button>
</form>

@isset($result)
    <div class="result">
        <h3>Answer:</h3>
        <p>{{ $result['answer'] }}</p>
        
        <details>
            <summary>Show SQL Query</summary>
            <code>{{ $result['sql'] }}</code>
        </details>
        
        <details>
            <summary>Show Raw Results ({{ $result['rows'] }} rows)</summary>
            <pre>{{ json_encode($result['results'], JSON_PRETTY_PRINT) }}</pre>
        </details>
    </div>
@endisset
```

## 📊 Example Questions

Here are some example questions you can ask:

### Simple Queries
```php
DB::ask('How many users are there?')
DB::ask('Show me all products')
DB::ask('What is the latest order?')
```

### Aggregations
```php
DB::ask('What is the total revenue?')
DB::ask('What is the average order value?')
DB::ask('How many orders per day this week?')
```

### Filtering
```php
DB::ask('Show me users who joined this month')
DB::ask('Find orders over $100')
DB::ask('List active subscriptions')
```

### Sorting and Limiting
```php
DB::ask('Show me the top 10 customers by spending')
DB::ask('What are the 5 most expensive products?')
DB::ask('List recent orders, newest first')
```

### Complex Queries
```php
DB::ask('Which products have never been ordered?')
DB::ask('Show me users who have not ordered in 30 days')
DB::ask('What is the average time between orders for each customer?')
```

### Joins
```php
DB::ask('Show me all orders with customer names')
DB::ask('List products with their category names')
DB::ask('Find users with their total number of orders')
```

## 🔒 Security Considerations

### Strict Mode (Recommended)

Always keep strict mode enabled in production:
```env
AI_DATABASE_STRICT_MODE=true
```

This prevents:
- Data modification (`INSERT`, `UPDATE`, `DELETE`)
- Schema changes (`ALTER`, `DROP`, `CREATE`)
- Privilege changes (`GRANT`, `REVOKE`)

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🔐 Security

If you discover any security-related issues, please email mostafaarafat199@gmail.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Your Name](https://github.com/mostafaarafatt)
- [All Contributors](../../contributors)

## 💬 Support

- **Documentation**: [Full documentation](https://github.com/mostafaarafatt/laravel-ai-database)
- **Issues**: [GitHub Issues](https://github.com/mostafaarafatt/laravel-ai-database/issues)
- **Discussions**: [GitHub Discussions](https://github.com/mostafaarafatt/laravel-ai-database/discussions)

## 🌟 Show Your Support

If this package helps you, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📖 Improving documentation
- 🔀 Contributing code

---

Made with ❤️ by [Mostafa Arafat](https://github.com/mostafaarafatt)
