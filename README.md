# PostgresAdmin

A browser-based RDBMS built on Laravel and Vue.js.

## Getting Started

Copy env.example and save as .env. Review /config/database.php and set up a "users" database for users of this application. The "dyanmic" configuration is used by the app when switching between databases.

### Prerequisites

Latest version of NPM to build assets; this app is set up to compile Vue.js files locally.

```
npm run watch
```

### Installing

Clone this repository and set up the environmental variables in a .env file in the project root. Update the UsersTableSeeder to include at least on administrator account.
Run the database migrations.

```
php artisan migrate --seed
```

Once that completes you should be able to log in and set up database connections.

## Built With

* [Laravel](https://laravel.com) - The web framework used
* [Vue.js](https://vuejs.org) - Front-end structure and components

## Contributing

This project is open-source and open to Pull Requests.

## Authors

* **Allen McCabe** - *Initial work*

## License

This project is licensed under the MIT License - see the [LICENSE](blob/master/LICENSE) file for details