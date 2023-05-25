# gapi

Generic API - PHP

// Based on https://www.leaseweb.com/labs/2015/10/creating-a-simple-rest-api-in-php/

# Concept

Many systems are built with an API backend. Building an API can be a lot of work so this project is built to make it a lot quicker and easier to build a RESTful API against a mySQL database. The project allows the configuration of the api to control which tables and which fields can be accessed through the API.

A RESTful API can also manage business logic. Simple business logic can added through various callbacks (not yet implemented)

# The Idea

When I created this PHP project the idea was to create an as simple as possible way of adding a REST API on top of any existing mySQL database. The original concept had the following goals:
1. Create a standard REST API that can connect to any table in the database (Done)
2. Make the API Configurable so through configuration the list of tables can be set, and the fields that can be seen, updated, deleted (Done)
3. Add Call backs for Pre/POST functionality (Allows additional security) (Done)
4. Auto Document the API with the correct Call (Needs to be redone)
5. Modify Get to include Paging (Pagination: Done, use offset and limit)
6. Add a Search option that does standard get with parameters. (Can be Post or Get)
7. _Modify Get to include Sorting option (TODO)_
8. Use prepared statements to prevent SQL Injection (Done)

# Demos

Create Demo database and Demo config (TODO)
Create example Javascript files to demo the API (TODO)

# Future

Once I got the basic library working I identified the following additional functionalities to add:
1. Allow calls to load child objects as Collections within the JSON (Done)
1.a. String Selects also support subkeys (Done)
2. Consider modifying POST to update records as well if id is sent in the path (Done)
2. Consider modifying DELETE to update bulk delete if a search collection is included (and no ID is sent) (Done)
3. Records can be Deleted based on a search collection being sent instead of an id (See below for a seach collection format) (Done)
4. _Bulk insert using a collection of records as part of POST (TODO)_
5. _Bulk update using search collection (TODO)_
6. _Manage security using an Auth library and tokens (TODO)_
7. _Create an array of fields that may be used in a search collection, eg so only indexed fields can be searched (TODO)_
8. _Modifiy the documentation to be valid swagger format :O (TODO)_
9. Triggers - before and after (Done)

# Known issues

The documentation option is not valid anymore due to options being added to manage CORS. Will be moved to new functionality, possibly called if a get is made to an imaginary table called swagger (TODO)

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
					"create" => Array("chatid","username","message")  <=== Limit which fields can be detailed when new record is created (note id in this case is auto)
				),
    "user" => Array(
					"key" => "id",
					"select" => Array("id","username","fullname"),
					"afterselect" => "functionName", <=== Creates a trigger that is called after any select passing the results, returns a modified results object 
					"update" => Array("fullname"),
					"delete" => false, <=== Prevent API from deleting
					"create" => false  <=== Prevent API from creating records
					"subkeys" => Array( <== sub takes eg /company/23/employees [Read Only]
						"property" => Array( <== sub name for url
							"key" => "user_id", <== key value in child table
							"tablename" => "member", <== child table name - if not same as name>
							"select" => array("id", "user_id", "app_id", "role") <== fields to return in select array
						)
					)
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

Using the API to interact with the Database

```HTTP
GET http://<server>/<project>/api.php/<tablename> <== Returns all records in table
GET http://<server>/<project>/api.php/<tablename>/count <== Returns count of records in table
GET http://<server>/<project>/api.php/<tablename>/<id> <== Returns single record on ID (note use of Key to define ID field in the config)
GET http://<server>/<project>/api.php/<tablename>?offset=<number>&limit=<number> <== Returns records in table starting from Offset and returning Limit rows

POST http://<server>/<project>/api.php/<tablename> <== creates new record - note fields to be included in formdata - returns new record key

PUT http://<server>/<project>/api.php/<tablename>/<id> <== creates new record - note fields to be included in formdata - note the call must use x-www-form-urlencoded

DELETE http://<server>/<project>/api.php/<tablename>/<id> <== Deletes record based on id
DELETE http://<server>/<project>/api.php/<tablename>/ <== Deletes record based on where collection
```

# Triggers

afterselect <== called after GET and search has retrieieved data, passes the result set and expects modified result set returned
beforeinsert, afterinsert
beforeupdate, afterupdate
beforedelete, afterdelete

before* <== called before action is taken, passes the details recieved by the api (eg Table and query params or post data), expects the same returned
	can be used to do validation checks (eg does the id being referenced exist)
after* <= called with details recieved by API and result of statement, no return value expected 
	can be used for Cascade events

# Search

Searches are done using Post:

```HTTP
POST http://<server>/<project>/api.php/<tablename>/search <== execute search - note fields to be included in formdata -- All fields added as AND
```

Fields to be included in formData are:
field: Comma seperated list of field names
op: Comma separated list of SQL operations (no validation is made on the op)
value: Comma separated list of values to be searched for

eg - Searches for all messages from user William that contain GAPI in them

```
field: username, message
op: =,like
value: William, %GAPI%
```

Sending a DELETE without an id in the path will execute a "delete .. where ..." using the same structure as for search


## Sample Database included

World Database:
https://dev.mysql.com/doc/index-other.html

