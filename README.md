# Medoo ORM Mapper

Hi! This is (my personal) Medoo ORM Mapping-Class for mapping the database based on Medoo into classes. 

[![CC BY-SA 4.0][cc-by-sa-shield]][cc-by-sa]

## Setup

Use composer to add the Mapper to your code:

```shell
composer require basteyy/medoo-orm
```

## Config

There are a few ways to config this.

### Create the Medoo-Instance by yourself

You can create the medoo instance somewhere in your code and than load the tables by passing it:

```php
$config = []; // Config 
$medoo = new \Medoo\Medoo($config);
$usersTable = new UsersTable($medoo);
```

### Use Dependency Injection

Create a definition somewhere in your code and simple call the tables:

```php
$DI->addDefinitions([
    \Medoo\Medoo::class => function () {
        $config = []; // Config 
        return new \Medoo\Medoo($config);
    }, 
    // OR (!!)
    'connection' =>  => function () {
        $config = []; // Config 
        return new \Medoo\Medoo($config);
    }, 
    // OR (!!) 
    'DB' =>  => function () {
        $config = []; // Config 
        return new \Medoo\Medoo($config);
    },  
    // OR (!!)
    'DatabaseConnection' =>  => function () {
        $config = []; // Config 
        return new \Medoo\Medoo($config);
    }
]);
```

### Passing the config as a array

You can simple pass the config as an array to the tables and let the script doing the instance-job:

```php
$config = []; // The Config
$usersTable = new FancyTableClassTable($config);
```



## License

This work is licensed under a
[Creative Commons Attribution-ShareAlike 4.0 International License][cc-by-sa].

[![CC BY-SA 4.0][cc-by-sa-image]][cc-by-sa]

[cc-by-sa]: http://creativecommons.org/licenses/by-sa/4.0/
[cc-by-sa-image]: https://licensebuttons.net/l/by-sa/4.0/88x31.png
[cc-by-sa-shield]: https://img.shields.io/badge/License-CC%20BY--SA%204.0-lightgrey.svg