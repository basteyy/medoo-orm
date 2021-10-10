# Medoo ORM Mapper

Hi! This is (my personal) Medoo ORM Mapping-Class for mapping the database based on Medoo into classes. 

[![CC BY-SA 4.0][cc-by-sa-shield]][cc-by-sa]

## Setup

Use composer to add the Mapper to your code:

```shell
composer require basteyy/medoo-orm
```

## Config



## Usage

See the examples for usecases / examples. But at the glance you need a class which extends the orm table class, define the id column and table-name and thats it.

## Examples

### Users Table Example
For example you have a users table called `users` like the following:

```
id | username | password | email | last_login
```

#### The UsersTable-Class

First you create the Table for that (database-)table:

```php
// File: UsersTable.php

// Define the class name and extend it with the Table Class
class UsersTable extends basteyy\MedooOrm\Table {

    // Abstract the database table
    
    //  the name of the database table (in this case users)   
    protected string $table_name = 'users';
    
    // define the id column (in this case id)
    protected string $id_column = 'id';
}
```

##### Select a user
```php
$
```

#### The UsersEntity-Class

## License

This work is licensed under a
[Creative Commons Attribution-ShareAlike 4.0 International License][cc-by-sa].

[![CC BY-SA 4.0][cc-by-sa-image]][cc-by-sa]

[cc-by-sa]: http://creativecommons.org/licenses/by-sa/4.0/
[cc-by-sa-image]: https://licensebuttons.net/l/by-sa/4.0/88x31.png
[cc-by-sa-shield]: https://img.shields.io/badge/License-CC%20BY--SA%204.0-lightgrey.svg