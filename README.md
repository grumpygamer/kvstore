# KVStore
Simple key/value store using PHP and SQLite.

This is intended for a low to medium traffic website.  If you need to use it on a high traffic website, switching the MySQL or PostgreSQL would be preferred to SQLite.  

KVStore supports the following operations:

```
get
put
inc
dec
add
```

## Creating the database

When creating the database make sure it is in a location where the web server has read/write access.

```
sqlite3 some/path/kv.db

CREATE TABLE kv_store (
  id INTEGER PRIMARY KEY,
  key TEXT,
  value TEXT,
  project TEXT,
  time TEXT
);
CREATE TABLE auth (
  id INTEGER PRIMARY KEY,
  project TEXT,
  secret TEXT,
  name TEXT,
  email TEXT,
  disabled INTEGER
);
CREATE INDEX idx_key ON kv_store(key ASC);
INSERT INTO auth (project,secret) VALUES ("my_project", "qskpchkqupdqydkz");
.quit
```

## Installing

Most modern installations of PHP already have the SQLite extention installed. If you're running an older version of PHP you will need to install the SQLite extention.

Place the `kvstore.php` file where the web server has access.

Copy `kvstore_inc.sample.php` to `kvstore_inc.php` and change `$DB_NAME = "some/path/kv.db"` to the location of the database file.

## Testing

Once install the key/value store can be tested with the following:

```
KVSTORE="https://example.com/kvstore.php?project=my_project&secret=qskpchkqupdqydkz"
curl -s -X POST -d "action=put" -d "key=games/favorite" -d "value=thimbleweedpark" $KVSTORE
curl -s -X POST -d "action=put" -d "key=test" -d "value=1" $KVSTORE
curl -s -X POST -d "action=inc" -d "key=test" $KVSTORE
curl -s -X POST -d "action=get" -d "key=games/favorite" $KVSTORE
```

To check the database:

```
sqlite3 some/path/kv.db

SELECT * FROM auth
SELECT * FROM kv_store
.quit
```



