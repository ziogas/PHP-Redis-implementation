<?php

require dirname(__DIR__).DIRECTORY_SEPARATOR.'redis.php';

class commandsTest extends PHPUnit_Framework_TestCase
{
    protected $redis;

    protected function setUp() {
        $this->redis = new redis_cli('127.0.0.1', 6379);
    }

    public function testSimpleVariables() {
        $this->redis->cmd('SET', 'foo', 'bar')->set();
        $value = $this->redis->cmd('GET', 'foo')->get();

        $this->assertSame('bar', $value);
    }

    public function testHset() {
        $this->redis->cmd('HSET', 'hash', 'foo', 'bar')
                    ->cmd('HSET', 'hash', 'abc', 'def')
                    ->cmd('HSET', 'hash', '123', '456')
                    ->set();

        $vals = $this->redis->cmd('HGETALL', 'hash')->get();
        $totalRecords = $this->redis->cmd('HVALS', 'hash')->get_len();

        $this->assertSame(array('foo', 'bar', 'abc', 'def', '123', '456'), $vals);
        $this->assertSame(3, $totalRecords);
    }

    public function testIncrements() {
        $this->redis->cmd('INCR', 'online_foo')
             ->cmd('INCR', 'online_bar')
             ->cmd('INCRBY', 'online_foo', 3)
             ->set();

        $totalOnlineUnique = (int)$this->redis->cmd('KEYS', 'online*')->get_len();
        $fooOnline = (int)$this->redis->cmd('GET', 'online_foo')->get();

        $this->assertSame(2, $totalOnlineUnique);
        $this->assertSame(4, $fooOnline);
    }

    public function testSets() {
        $hash = 'set_structure';
        $this->redis->cmd('SADD', $hash, 'Some data')->set();
        $this->redis->cmd('SADD', $hash, 'More data')->set();
        $this->redis->cmd('SADD', $hash, 'Even more data')->set();

        $this->redis->cmd('EXPIRE', $hash, 900)->set();

        $expiration = $this->redis->cmd('TTL', $hash)->get();
        $totalItems = $this->redis->cmd('SCARD', $hash)->get();
        $list = $this->redis->cmd('SMEMBERS', $hash)->get();
        sort($list);

        $this->redis->cmd('DEL', $hash)->set();
        $emptyList = $this->redis->cmd('SMEMBERS', $hash)->get();

        $this->assertSame(900, $expiration);
        $this->assertSame(3, $totalItems);
        $this->assertSame(array('Even more data', 'More data', 'Some data'), $list);
        $this->assertSame(array(), $emptyList);
    }

    protected function tearDown() {
        $keys = array(
            'foo',
            'online_foo',
            'online_bar',
            'hash',
            'set_structure',
        );

        foreach ($keys as $key) {
            $this->redis->cmd('DEL', $key)->set();
        }
    }
}
