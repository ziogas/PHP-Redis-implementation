PHP Redis implementation V2.1
==============

Simple and lightweight redis implementation, all commands are passed as is, so you have all the freedom to manage redis just like in redis-cli.
All "set" type commands can be chained and sended once to the redis server.
set_error_function () method allows to set custom error handler.

DOWNLOAD
--------------
You could checkout latest version with:

    $ git clone git://github.com/ziogas/PHP-Redis-implementation


INSTALL
--------------
To install PHP redis:

* Simply copy redis.php to your site and require it from external script

Here some examples:

function redis_error ( $error )
{
    throw new error ( $error );
}

$redis = new redis_cli ();
$redis -> set_error_function ( 'redis_error' );

$redis -> cmd ( 'SET', 'foo', 'bar' ) 
       -> cmd ( 'HSET', 'hash', 'field', 'val' )
       -> cmd ( 'EXPIRE', 300, 'foo' )
       -> set ();

$foo = $redis -> cmd ( 'GET', 'foo' ) -> get ();
$field = $redis -> cmd ( 'HGET', 'hash', 'field' ) -> get ();


More usage examples can be found on test.php


LICENSE
--------------
MIT


AUTHORS
-------------
Arminas Žukauskas
