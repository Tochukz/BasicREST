# A BASIC REST SERVICE WITH PHP#
This is a RESTfull API in its most basic form.
This API stores and returns items and links. Say for example an item such as a Shoe you found online and the uri for the shoe.
In JSON format this would be
```json
    {
      "name":"Classic Shoe",
      "link":Edgers.co.za"
    }
```
And for XML it would be 
```xml
    <item>
        <name>Classic Shoe</name>
        <link>Edger.co.za</link>
    </item>
```
The service is built with just three files: 
* index.php
* Storage.php (a class)
* storage.txt (storage file)

index.php acts like our controller and a wrapper over the Storage class. It calls the relevant method for every request.
Storage::class processes all request and return a response to index.php which passes it on to the client
storage.txt is where all records are stored

This services support 4 Content types: application/xml, application/json, text/html and text/plain. 
But  POST, PUT and DELETE request body must be in either JSON or XML format.

## EXAMPLE 
### GET REQUEST 
##### To get all items in the storage 
    http://rest.dev/index.php?item=all
##### To get just a single item in the storage
    http://rest.dev/index.php?item=itemname

### POST REQUEST
##### To add an item to the storage
###### JSON
    POST /index.php HTTP/1.1
    Host: rest.dev
    User-Agent: curl/7.55.0
    Accept:application/json
    Content-Type:application/json
    Content-Length: 56
    
    {
        "name":"Samsung Galaxy",
        "link":"http://samsung.org"
    }
    
### PUT REQUEST
##### To update an item in the storage
###### XML
    PUT /index.php HTTP/1.1
    Host: rest.dev
    User-Agent: curl/7.55.0
    Accept:application/xml
    Content-Type:application/xml
    Content-Length: 78
    
    <item><name>Samsung Galaxy</name><link>http://samsung-online.com</link></item>
    
### DELETE REQUEST
##### To delete a record from the storage
###### JSON
     DELETE /index.php HTTP/1.1
     Host: rest.dev
     User-Agent: curl/7.55.0
     Accept:application/json
     Content-Type:application/json
     Content-Length: 25
     
     {"name":"samsung galaxy"}
    
Every request having a body must be sent in either XML or JSON format




This is a DEMO showing how REST APIs work. 
It is its simplest form and not intended for production. 
Note that not security consideration was implemented for thie reason.

:tada :rocket








