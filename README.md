# Symfony + Vue test job

## How to launch

### docker-compose

```
docker-compose up
```
Go to http://localhost:8000

### Right on your host, without docker

You'll need:
* php with extensions: gd, pdo_mysql, zip.
* composer
* Symfony CLI
* npm
* A database. Create a DB, add a user, grant him permissions to that db.
* Configure symfony's DB connection in project's .env file.

```
npm run build
```

Then launch symfony's web server:
```
symfony serve
```

Go to http://localhost:8000
