# Agent guidelines for Dirigent development: migrations/

## Generate new migrations

To generate a new migration, execute the `symfony console doctrine:migrations:diff --nowdoc --formatted` command.

## Coding style

- Migration files must follow the naming convention of `Version[0-9]{14}.php`.
- Migrations must have a non-empty description.
- Queries should be wrapped in nowdoc by default. Only if a PHP variable is used in the query is it allowed to be wrapped in heredoc.

## Required columns

If a required (non-nullable) column is added to the schema, add it with the following queries:

1. Add a nullable column.
2. Set a default value for every row in the table.
3. Remove the nullable flag from the column.
