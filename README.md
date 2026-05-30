#The Order Analytics Dashboard is a full-stack web application that transforms raw e-commerce order data stored in a MySQL database into rich, 
interactive visual insights. Built entirely with native PHP, no frameworks required, it exposes a clean REST API backend and renders a dark-themed analytics frontend powered by Chart.js.

*What It Does*
•	Connects to a MySQL / MariaDB database containing Amazon order records
•	Exposes a REST API with 12+ endpoints for scorecards, charts, and paginated data
•	Renders a dark-themed dashboard with 5 interactive charts and 4 KPI scorecards
•	Automatically falls back to sample data when the database is unreachable
•	Updates all charts in real time whenever new data is inserted into the database

*Why It Is Useful*
•	Zero framework dependencies — runs on any standard XAMPP / WAMP / LAMP stack
•	Single-page dashboard — all insights visible without navigating between pages
•	Live data — refresh the page to see newly inserted orders reflected immediately
•	Modular architecture — repository pattern makes it easy to add new queries

*Problem It Solves*
E-commerce sellers on Amazon accumulate thousands of order rows in spreadsheets with no visual way to spot trends. This dashboard answers questions like: Which states generate the most orders? What size sells best? 
Is revenue growing month over month? Which courier status is failing? — all at a glance, without writing a single SQL query manually.


*Structures*
htdocs/
└── dashboard/
    ├── dashboard.php          ← Frontend view (fetches from API)
    └── api/
        ├── index.php          ← Entry point & autoloader
        ├── .htaccess          ← Apache mod_rewrite rules
        ├── config/
        │   ├── database.php   ← DB credentials
        │   └── app.php        ← CORS, cache, limits
        ├── src/
        │   ├── Database/
        │   │   └── Connection.php      ← PDO singleton
        │   ├── Repository/
        │   │   └── OrderRepository.php ← All SQL queries
        │   ├── Response/
        │   │   └── JsonResponse.php    ← JSON output wrapper
        │   └── Router.php              ← Lightweight router
        └── routes/
            └── api.php        ← All endpoint definitions
