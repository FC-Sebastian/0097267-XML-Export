# Google review exporter

## Installation guide
Open the file config.sample.php.  
You will see an array that you will need to fill with your values.  
**"url"** should be a URL which points to this projects' directory.  
**"dbhost"**, **"dbuser"** and **"dbpass"** are your database's host, username and password respectively.  
**"db"** should be the database where your articles and reviews are stored and where exported reviews will be stored.  
Afterwards rename **config.sample.php** to **config.php**.  

## Using the exporter
To use the exporter you will need to open the URL you specified in the config.php which should look like: "yourURL.com/directoryOfExporter/".  
To export reviews click either "export all reviews" to export all reviews in the database,  
or "export new reviews" to only export reviews which weren't previously exported.