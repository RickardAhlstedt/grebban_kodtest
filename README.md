# Grebban kodtest

## PHP
Written using Laravel 11 with a local database in sqlite.

### Testing
Please checkout the repo and install it locally:
```
git clone git@github.com:RickardAhlstedt/grebban_kodtest.git
cd grebban_kodtest/php
composer install && npm install
php artisan serve
```

For testing the API-routes, I used insomnia which is an alternative to Postman, when you have the application up and running you can get data from the following route:
`http://127.0.0.1:8000/api/v1/products`

The following parameters are supported:  
| parameter | description | default |
| --- | --- | --- |
| page | What page to return from the result | 1 |
| page_size | How many results per page to list in the result, set to `0` or `-1` for all | 5 |

### Time spent
As I'm currently on parental leave, getting time for this test has consisted on a few hours here and there when the kids are asleep; but in total I spent ~5-6hrs spread over a week.  
A lot of this time was to freshen up on the documentation for Laravel as I'm more used to Symphony and Pimcore which I have spent the last 2 years working in.