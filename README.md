autotrader-miner
================

Single-user site written in [RIFT](https://github.com/strawp/RIFT) for mining Autotrader.co.uk data and providing more useful searches for cars.

Provide the site with your local fueld prices, approximate annual mileage and search URLs and it will pick up all cars on Autotrader matching those searches and work out for
you useful searchable data:

 * Whether the car has air con and cupholders
 * What the total cost of ownership over 3 years for the car is 

Save time by filtering cars that:

 * Don't go from 0-62 quickly enough
 * Would cost too much to run
 * Search for a car in any colour EXCEPT yellow
 * Filter on multiple criteria instead of just one, e.g. show all VW, Audi, Seat and Skodas

Order results by fields "hidden" on Autotrader:

 * 0-62 time
 * Fuel consumption
 * Cost of ownership
 * Top speed


## Dependencies

 * Apache 2
    * mod rewrite
 * PHP 5
    * php5-curl
    * php5-mysql
 * Mysql Server

Preferably a Linux of some kind, should work OK under Windows though.

## Installation

1. Install dependencies, enable mod rewrite with `sudo a2enmod rewrite`
2. Clone into a folder somewhere, e.g. 

```
cd /var/www
git clone https://github.com/strawp/autotrader-miner.git
```

3. Configure Apache
  * Enable _htaccess: 

`$ sudo vim /etc/apache2/htaccess.conf`

`AccessFileName .htaccess _htaccess`

..and also hide files starting "_ht" or ".ht"

```
<Files ~ "^(\.|_)ht">
    Order allow,deny
    Deny from all
    Satisfy all
</Files>
```

  * Point the vhost to the right directory:

```
<VirtualHost localhost:80>
  ServerName localhost
  DocumentRoot /var/www/autotrader-miner/application/
</VirtualHost>
```

Restart Apache:

`sudo apache2ctl restart`

4. Edit site settings

`$ vim /var/www/autotrader-miner/application/core/settings.php`

Set `DB_*` settings to something sensible.

Create a database for it to connect to:

`$ mysql -u root -p`

`mysql> create schema cars;`

Add a user to that database:

`mysql> GRANT ALL ON cars.* TO cars IDENTIFIED BY 'cars';`

`mysql> FLUSH PRIVILEGES;`

`mysql> exit`

5. Initialise the database structure, noting the password for the admin user that is created for you as part of this process.

`cd autotrader-miner/application/scripts`

`php sync_db.php`

6. Log in at whatever URL you deployed it to using the admin username / password the `sync_db.php` script created. If you missed that bit, 
at this stage you can just drop and recreate the schema for it to do it all again using `mysql> DROP SCHEMA cars`.

7. OR run in headless mode. Add a search using e.g. `php scripts/addsearch.php http://www2.autotrader.co.uk/search/used/cars/postcode/cv12ue/radius/10/sort/default/onesearchad/used%2Cnearlynew%2Cnew`
then just query the database directly.

8. Run the searches by running `php scripts/runsearches.php`. This will run all searches that haven't been run for more than 12 hours. Only car pages that aren't already in the DB will be scraped.




