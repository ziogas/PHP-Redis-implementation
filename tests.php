<?php

require dirname ( __FILE__ ) .'/redis.php';

define ( 'NL', ( PHP_SAPI == 'cli' ? "\n" : '<br />' ) );

$redis = new redis_cli ( '127.0.0.1', 6379 );
//$redis -> cmd ( 'SELECT', 9 );

/////////////////////////////////////////////////////////////

$redis -> cmd ( 'SET', 'foo', 'bar' ) -> set ();
$foo = $redis -> cmd ( 'GET', 'foo' ) -> get ();
var_dump ( $foo );

echo NL . str_repeat ( '-', 20 ) . NL . NL;

/////////////////////////////////////////////////////////////

$redis -> cmd ( 'HSET', 'hash', 'foo', 'bar' ) -> cmd ( 'HSET', 'hash', 'abc', 'def' ) -> cmd ( 'HSET', 'hash', '123', '456' ) -> set ();

$vals = $redis -> cmd ( 'HGETALL', 'hash' ) -> get ();
var_dump ( $vals );

$total = $redis -> cmd ( 'HVALS', 'hash' ) -> get_len ();
var_dump ( $total );

echo NL . str_repeat ( '-', 20 ) . NL . NL;

/////////////////////////////////////////////////////////////

$redis -> cmd ( 'INCR', 'online_foo' ) -> cmd ( 'INCR', 'online_bar' ) -> cmd ( 'INCRBY', 'online_foo', 3 ) -> set ();

$total_online = $redis -> cmd ( 'KEYS', 'online*' ) -> get_len ();
var_dump ( $total_online );

$foo_online = $redis -> cmd ( 'GET', 'online_foo' ) -> get ();
var_dump ( $foo_online );

echo NL . str_repeat ( '-', 20 ) . NL . NL;

/////////////////////////////////////////////////////////////

$hash = 'GO-'. date ( 'dHi' );
$redis -> cmd ( 'SADD', $hash, 'Some data' )
       -> cmd ( 'SADD', $hash, 'More data' )
       -> cmd ( 'SADD', $hash, 'Even more data' )
       -> cmd ( 'EXPIRE', $hash, 900 )
       -> set ();

$expiration = $redis -> cmd ( 'TTL', $hash ) -> get ();
var_dump ( $expiration );

$total = $redis -> cmd ( 'SCARD', $hash ) -> get ();
var_dump ( $total );

$list = $redis -> cmd ( 'SMEMBERS', $hash ) -> get ();
var_dump ( $list );

$redis -> cmd ( 'DEL', $hash ) -> set ();
$list = $redis -> cmd ( 'SMEMBERS', $hash ) -> get ();
var_dump ( $list );

echo NL . str_repeat ( '-', 20 ) . NL . NL;

/////////////////////////////////////////////////////////////

//$redis -> cmd ( 'FLUSHDB' ) -> set ();
