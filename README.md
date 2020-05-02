# gapi

Generic API - PHP

// Based on https://www.leaseweb.com/labs/2015/10/creating-a-simple-rest-api-in-php/

# Concept

Many systems are built with an API backend. Building an API can be a lot of work so this project is built to make it a lot quicker and easier to build a RESTful API against a mySQL database. The project allows the configuration of the api to control which tables and which fields can be accessed through the API.

A RESTful API can also manage business logic. Simple business logic can added through various callbacks (not yet implemented)

# The Idea

1. Create a standard REST API that can connect to any table in the database (Done)
2. Make the API Configurable so through configuration the list of tables can be set, and the fields that can be seen, updated, deleted (Done)
3. Add Call backs for Pre/POST functionality (Allows additional security)
4. Auto Document the API with the correct Call (Needs to be redone)
5. Modify Get to include Paging (Pagination: Done, use offset and limit)
6. Add a Search option that does standard get with parameters. (Can be Post or Get)
7. Modify Get to include Sorting option
8. Use prepared statements to prevent SQL Injection (Done)

# Demos

Create Demo database and Demo config (TODO)
Create example Javascript files to demo the API (TODO)

# Future

1. Allow calls to load child objects as Collections within the JSON
2. Consider modifying POST to update records as well if id is sent in the path (Done)
3. Records can be Deleted based on a serch collection being sent instead of an id (See Post for a seach collection format) (Done)

# Installation

Copy the api.php and apicore.php to your server directory. Open the api.php file and set the Database Connection values (server, username, password, schema). Also copy gapi_document.php to enable the auto documentation of your API through the OPTIONS method. If this file is excluded the API will be kept private and an error will be thrown instead of documentation being displayed.

# Usage

Modify the Config structure (api.php) with the details of your tables

```PHP
$config = Array(
	"database" => Array("server" => 'localhost',
					"username" => 'username',
					"password" => 'password',
					"database" => 'schema'),
    "messages" => Array(
					"key" => "id",  <=== Define the key field for the table in the database
					"select" => Array("chatid","username","message","createddate"), <=== Limit which fields acan be selected
					"update" => Array("message"), <=== Limit which fields can be updated
					"delete" => true,  <=== Allow deletions
					"create" => Array("chatid","username","message")  <=== Limit which fields can be detailed when new record is created (not id in this case is auto)
				),
    "user" => Array(
					"key" => "id",
					"select" => Array("id","username","fullname"),
					"update" => Array("fullname"),
					"delete" => false, <=== Prevent API from deleting
					"create" => false  <=== Prevent API from creating records
				),
	"chats" => Array(
					"tablename" => "chat",   <=== Note renaming of table - API converts incoming 'chats' to the correct table name chat
					"key" => "id",
					"select" => Array("id","name"),
					"update" => false, <=== Prevent API from updating records
					"delete" => false,
					"create" => false
	)
);
```

Note select can also be set to false to prevent selecting of records (eg a monitoring end point where the systems should only be able to create new records)

Using the API to fetch Database

```HTTP
GET http://<server>/<project>/api.php/<tablename> <== Returns all records in table
GET http://<server>/<project>/api.php/<tablename>/count <== Returns count of records in table
GET http://<server>/<project>/api.php/<tablename>/<id> <== Returns single record on ID (note use of Key to define ID field in the config)
GET http://<server>/<project>/api.php/<tablename>?offset=<number>&limit=<number> <== Returns records in table starting from Offset and returning Limit rows

POST http://<server>/<project>/api.php/<tablename> <== creates new record - note fields to be included in formdata - returns new record key

PUT http://<server>/<project>/api.php/<tablename>/<id> <== creates new record - note fields to be included in formdata - note the call must use x-www-form-urlencoded

DELETE http://<server>/<project>/api.php/<tablename>/<id> <== Deletes record based on id
```

# Search

Searches are done using Post:

```HTTP
POST http://<server>/<project>/api.php/<tablename>/search <== execute search - note fields to be included in formdata -- All fields added as AND
```

Fields to be included in formData are
field: Comma seperated list of field names
op: Comma separated list of SQL operations
value: Comma separated list of values to be searched for

eg - Searches for all messages from user William that contain GAPI in them

```
field: username, message
op: =,like
value: William, %GAPI%
```
