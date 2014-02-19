# QueryParser

This project is a different approach to separate your queries search to run into database, 
when a simple (or comples) ORM is too complicated to run all your queries or simply can't do what you need, 
you can use QueryParser for put your queries into a YAML file and easily get from source

## Example

```php
<?php
$queryParser = new QueryParser('config/queries.yml');
$path = 'queries.user.login';
$query = $queryParser->findQuery($path);
$query = $queryParser->replaceValues($query, array('id' => 1));

echo $query;
```