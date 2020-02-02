# Web Applications semestral work 

## Installation
This project requires PHP 7.2. Composer dependencies must be installed using 
`composer install`. The database schema is the same as the 
[recommended semestral work's](https://webik.ms.mff.cuni.cz/semestralwork/db_sample.sql).
The database connection is configured with the `db_config.php` file. A sample
file is provided as `db_config.php.sample` (please note the slight difference
with the recommended semestral work template).

## URL rewriting

URL rewriting is used to redirect the requests to the `webroot` folder. 
`.htaccess` files should work properly on Apache2 if authorized.

## Documentation

The documentation for this project is located in the `docs` folder.
