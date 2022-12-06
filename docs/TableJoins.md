## Usage

Table User

| ID | Username | Name | Things 

Table Login

| ID | UID | Password 

Connect TableLogin on TableUser:

TableLogin UID points on TableUser ID. The Join in the UserTable::class:

```php 
// UserTable.php
class UserTable
extends \basteyy\MedooOrm\Table
implements \basteyy\MedooOrm\Interfaces\TableInterface
{
    protected string $table_name = 'user';
    protected string $id_column = 'id';
    protected array $join = [
        LoginTable::class => ["id" => "uid" ]
    ];
    // Local ID and remote ID
    // In this case id and uid
}

// LoginTable.php
class LoginTable
extends \basteyy\MedooOrm\Table
implements \basteyy\MedooOrm\Interfaces\TableInterface
{
    protected string $table_name = 'login';
    protected string $id_column = 'id';
}
```