MySQLManager
============

Manage MySQL statements with PHP easily using OOP inherited methods

## Installing
You need first to include `mysqlmanager.php` and `mysqliadapter.php`, these files includes `MySQLManager` and `MySQLIAdapter` classes.

```php
<?php
// require library classes
require './mysqlmanager.php';
require './mysqliadapter.php';

// setting connecting configuration
MySQLIAdapter::config(array(
	'db_server'     => 'localhost',
	'db_user' 		  => 'root',
	'db_password' 	=> '',
	'db_name' 		  => 'onyx'
));

// getting connection singleton method
$db = MySQLIAdapter::getConnection();
```
#### Select Statement
```php
<?php
// selecting from just one table
$db->select($rows)->table('tbl_name')->execute();

// selecting from more than one table
 $db ->select( 'fields from table_1' )
			->table( 'table_1 ' )
		->join(  'table_2', 'type of join(left|right|join)' )
			->select( 'fields from table_2' )
				->on( 'table_2.field = table_1.field' )
		->join( 'table_3, type of join(left|right|join)' )
			->select( 'fields from table_3'  )
				->on( 'table_3.field = table_1.field' )

```
#### Create statement
```php
<?php
// creating database 
$db->create()->database('db_name')->execute();

// creating tables
$db->create()->table('tbl_name', array(
  'id' => 'int primary auto_inc'
  'name' => 'varchar|20 NOT NULL'
))->execute();

// creating rows
$db->create()->row('row_name', array(
  'varchar|20 NOT NULL'
))->table('tbl_name')->execute();
```
`primary`, `auto_inc` and `varchar|20` are automatically transferred to their equivalent

#### Managing tables data
```php
<?php
// inserting data
# inserting array's keys are same as table fields
$db->insert(array(
  'id' => NULL,
  'name' => 'Ali Saleh'
))->table('tbl_name')->execute();

// updating rows
$db->update(updateArray)->table('tbl_name')->where('id', '=', 10)->execute();

// deleting mysql result rows
$db->delete()->table('tbl_name')->where('id', '>', 10)->execute();

// delete all table's data
$db->truncate()->table('tbl_name')->execute();
```
`Insert` and `update` parameter must be an array of `mysql_row => value`

#### Drop statement
```php
<?php
// dropping database
$db->drop()->database('db_name')->execute();

// dropping tables
$db->drop()->table('db_name')->execute();

// dropping table row
$db->drop()->row('tbl_row')->table('tbl_name');
```

#### Information about last performed query
``` php
<?php
// last inserted id of AUTO_INCREMENT table row
$db->insert(array(
  'name' => 'Salma'
))->table('tbl_name')->execute()->inserted();

// number of affected rows
$db->update(array(
))->table('tbl_name')->where('name', '=', 'salma')->execute()->affected();

// number of rows of last select
$db->select('*')->table('tbl_name')->execute()->rows();

// fetch array of last select statement
$db->select('*')->table('tbl_name')->execute()->result()
```

### License
This project is licensed under the CC0 1.0 Universal license. See the LICENSE file for details
