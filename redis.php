<?php

/**
 * Very simple Redis implementation, all commands passed in cli format
 * Add commands via cmd ( $command [, $variable1 [, $variable2 ] ] ) method
 * Fire commands via exec () o set () methods ( first one will return output, usefull for get operations )
 *
 * Usage:
 * $redis = new redis_cli ();
 * $redis -> cmd ( 'SET', 'foo', 'bar' ) -> set ();
 * $foo = $redis -> cmd ( 'GET', 'foo' ) -> get ();
 *
 * $redis -> cmd ( 'HSET', 'hash', 'foo', 'bar' ) -> cmd ( 'HSET', 'hash', 'abc', 'def' ) -> set ();
 * $vals = $redis -> cmd ( 'HVALS', 'hash' ) -> get ();
 *
 * $redis -> cmd -> ( 'KEYS', 'online*' );
 * $total_online = $redis -> get_len ();
 *
 */
class redis_cli
{ 
    const INTEGER = ':';
    const INLINE = '+';
    const BULK = '$';
    const MULTIBULK = '*';
    const ERROR = '-';

    private $handle = false;
    private $host;
    private $port;
    private $silent_fail;

    private $commands = array ();
    private $max_response_len = 1048576;
    private $force_reconnect = false;
    private $timeout = 180;
   
    public function __construct ( $host = false, $port = false, $silent_fail = false )
    {
        if ( $host && $port )
        { 
            $this -> connect ( $host, $port, $silent_fail );
        }
    }

    public function connect ( $host = '127.0.0.1', $port = 6379, $silent_fail = false )
    {
        $this -> host = $host;
        $this -> port = $port;
        $this -> silent_fail = $silent_fail;

        if ( $silent_fail )
        { 
            $this -> handle = @fsockopen ( $host, $port, $errno, $errstr );

            if ( !$this ->  handle )
            { 
                $this -> handle = false;
            }
        }
        else
        { 
            $this -> handle = fsockopen ( $host, $port, $errno, $errstr );
        }

        if ( is_resource ( $this -> handle ) )
        { 
            stream_set_timeout ( $this -> handle, $this -> timeout );
        }
    }

    public function reconnect (  )
    {
        $this -> __destruct ();
        $this -> connect ( $this -> host, $this -> port, $this -> silent_fail );
    }

    public function __destruct ()
    {
        if ( is_resource ( $this -> handle ) )
        { 
            fclose ( $this -> handle );
        }
    }

    public function commands ()
    {
        return $this -> commands;
    }

    public function cmd ()
    {
        if ( !$this -> handle )
        { 
            return $this;
        }

        $args = func_get_args ();
        $rlen = count ( $args );

        $output = '*'. $rlen . "\r\n";

        foreach ( $args as $arg )
        {       
            $output .= '$'. strlen ( $arg ) ."\r\n". $arg ."\r\n";
        }

        $this -> commands [] = $output;
        return $this;
    }

    public function set ()
    {
        if ( !$this -> handle )
        { 
            return false;
        }

        $return = $this -> exec ();

        if ( $this -> force_reconnect )
        { 
            $this -> reconnect ();
        }

        return fgets ( $this -> handle, 32 );
    }

    public function get ( $line = false )
    {
        if ( !$this -> handle )
        { 
            return false;
        }

        $command = $this -> exec ();

        if ( $command )
        {
            $return = trim ( fgets ( $this -> handle, 1024 ) );
            $char = substr ( $return, 0, 1 );
            $return = substr ( $return, 1 );

            if ( $char === self::INLINE || $char === self::INTEGER || $char === self::ERROR )
            {
                if ( $this -> force_reconnect )
                { 
                    $this -> reconnect ();
                }

                //we dont need anything do do
                return $return;
            }
            elseif ( $char === self::BULK )
            { 
                if ( $return === '-1' )
                { 
                    $return = null;
                }
                else
                { 
                    $return = $this -> read_bulk_response ( $return );
                }

                if ( $this -> force_reconnect )
                { 
                    $this -> reconnect ();
                }

                return $return;

            }
            elseif ( $char === self::MULTIBULK )
            { 
                if ( $return === '-1')
                {
                    return null;
                }

                $response = array ();

                for ( $i = 0; $i < $return; $i++ )
                {
                    $tmp = trim ( fgets ( $this -> handle, 1024 ) );

                    if ( $tmp === '-1' )
                    {
                        $response [] = null;
                    }
                    else
                    {
                        $response [] = $this -> read_bulk_response ( $tmp );
                    }
                }

                if ( $this -> force_reconnect )
                { 
                    $this -> reconnect ();
                }

                return $response;
            }

            if ( $this -> force_reconnect )
            { 
                $this -> reconnect ();
            }

            return false;
        }
    }

    public function get_len ()
    {
        if ( !$this -> handle )
        { 
            return false;
        }

        $command = $this -> exec ();

        if ( $command )
        {
            $return = explode ( "\r\n", trim ( fgets ( $this -> handle, 32 ) ) );
        }

        return substr ( $return [ 0 ], 1 );
    }

    private function exec ()
    {
        if ( sizeof ( $this -> commands ) < 1 )
        { 
            return null;
        }

        $command = implode ( "\r\n", $this -> commands ) ."\r\n";
        fwrite ( $this -> handle, $command );

        $this -> commands = array ();
        return $command;
    }

    public function set_force_reconnect ( $flag )
    {
        $this -> force_reconnect = $flag;
        return $this;
    }

    public function parse_multibulk_response ( $str )
    {
        preg_match_all ( '#\$\d+\r\n(.+)(\r\n)?#mi', $str, $matches );

        $return = array ();

        foreach ( $matches [ 1 ] as $match )
        { 
            $return [] = trim ( $match );
        }

        return $return;
    }

    public function parse_bulk_response ( $str )
    {
        preg_match ( '#\$\d+\r\n(.*?)(\r\n)?#Umis', $str, $matches );
        return trim ( $matches [ 1 ] ) ;
    }

    private function read_bulk_response ( $tmp )
    { 
        $response = null;

        $read = 0;
        $size = substr ( $tmp, 1 );

        while ( $read < $size )
        {
            $diff = $size - $read;

            $block_size = $diff > 2048 ? 2048 : $diff;

            $response .= fread ( $this -> handle, $block_size );
            $read += $block_size;
        }

        fread ( $this -> handle, 2 );

        return $response;
    }
}
