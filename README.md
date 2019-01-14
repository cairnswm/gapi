# gapi
Generic API - PHP

# Idea

1. Create a standard REST API that can connect to any table in the database (Done)
2. Make the API Configutrable so through configuration the list of tables can be set, and the fields that can be seen, updated, deleted (Done)
3. Add Call backs for Pre/POST functionality (Allows additional security) 
4. Auto Document the API with the correct Call
5. Modify Get to include Sorting and Paging (Pagination: Done)
6. Add a Search option that does standard get with parameters. (Can be Post or Get)

# Demos

Create Demo database and Demo config
Create example Javascript files to demo the API

# Future

1. Allow calls to load child objects as Collections within the JSON
2. Respond with XML instead of JSON
3. ? Convert to a class so that Callbacks are created as child object functions ?


# Installation

Copy the api.php to your server directory. Open the file and set the Database Connection values (server, username, password, schema).

# Usage

Moldify the Config structure with the details of your tables

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

Note select can also be set to false to prevent selecting of records (eg a monitoring end point where the systems should only be able to create new records)




