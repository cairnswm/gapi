
 # gapi
Generic API - PHP

# Concept

Many systems are built with an API backend. Building an API can be a lot of work so this project is built to make it a lot quicker and easier to build a RESTful API against a mySQL database. The project allows the configuration of the api to control which tables and which fields can be accessed through the API.

A RESTful API can also manage business logic. Simple business logic can added through various callbacks (not yet implemented)

# Idea

1. Create a standard REST API that can connect to any table in the database (Done)
2. Make the API Configutrable so through configuration the list of tables can be set, and the fields that can be seen, updated, deleted (Done)
3. Add Call backs for Pre/POST functionality (Allows additional security) 
4. Auto Document the API with the correct Call
5. Modify Get to include Sorting and Paging (Pagination: Done)
6. Add a Search option that does standard get with parameters. (Can be Post or Get)
7. Add Sorting option

# Demos

Create Demo database and Demo config
Create example Javascript files to demo the API

# Future

1. Allow calls to load child objects as Collections within the JSON
2. Respond with XML instead of JSON
3. ? Convert to a class so that Callbacks are created as child object functions ?
4. Consider modifying POST to update records as well if id is sent in the path


# Installation

Copy the api.php to your server directory. Open the file and set the Database Connection values (server, username, password, schema).

# Usage

Moldify the Config structure with the details of your tables

```PHP
$config = Array(
    "messages" => Array(
					"key" => "id",  <=== Define the key field in the database
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
					"tablename" => "chat",   <=== Note renaming of table - API converts incoming chats to the correct table name chat
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
GET http://<server>/<project>/api.php/<tablename> <== Returns all  records in table
GET http://<server>/<project>/api.php/<tablename>/<id> <== Returns single record on ID (note use of Key to define ID field in the config)
GET http://<server>/<project>/api.php/<tablename>?offet=<number>&limit=<number> <== Returns records in table starting from Offset and returning Limit rows

POST http://<server>/<project>/api.php/<tablename> <== creates new record - note fields to be included in formdata

PUT http://<server>/<project>/api.php/<tablename>/<id> <== creates new record - note fields to be included in formdata - note the call must use x-www-form-urlencoded

DELETE http://<server>/<project>/api.php/<tablename>/<id> <== Deletes record based on id
```
 


