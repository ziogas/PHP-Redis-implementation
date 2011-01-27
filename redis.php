<?php

/**
 * Very simple Redis implementation, all commands passed in cli format
 * Add commands via cmd ( $command [, $variable1 [, $variable2 ] ] ) method
 * Fire commands via get () or set () methods ( first one will return output, usefull for get operations )
 *
 * Usage:
 * $redis = new redis_cli ();
 * $redis -> cmd ( 'SET', 'foo', 'bar' ) -> set ();
 * $foo = $redis -> cmd ( 'GET', 'foo' ) -> get ();
 *
 * $redis -> cmd ( 'HSET', 'hash', 'foo', 'bar' ) -> cmd ( 'HSET', 'hash', 'abc', 'def' ) -> set ();
 * $vals = $redis -> cmd ( 'HVALS', 'hash' ) -> get ( 0 );
 * $vals = $redis -> parse_multibulk_response ( $vals );
 *
 */
class redis_cli
{ 
    private $handle = false;
    private $commands = array ();
   
    public function __construct ( $host = false, $port = false, $silent_fail = false )
    {
        if ( $host && $port )
        { 
            $this -> connect ( $host, $port, $silent_fail );
        }
    }

    public function connect ( $host = '127.0.0.1', $port = 6379, $silent_fail = false )
    {
        if ( $silent_fail )
        { 
            try
            { 
                $this -> handle = fsockopen ( $host, $port, $errno, $errstr );
            } 
            catch ( Exception $e )
            { 
                $this -> handle = false;
            }
        }
        else
        { 
            $this -> handle = fsockopen ( $host, $port, $errno, $errstr );
        }
    }

    public function __destruct ()
    {
        if ( is_resource ( $this -> handle ) )
        { 
            fclose ( $this -> handle );
        }
    }

    public function commands (  )
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

        return $this -> exec ();
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
            $return = trim ( fread ( $this -> handle, 4096 ) );

            //return only selected line / last line
            if ( $line !== 0 )
            { 
                $return = explode ( "\r\n", $return );

                if ( $line && isset ( $return [ $line - 1 ] ) )
                { 
                    $return = $return [ $line - 1 ];
                }
                else
                { 
                    $return = end ( $return );
                }
            }

            $return = trim ( $return, "\r\n " );

            return $return === '$-1' ? null : $return;
        }
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

    public function parse_multibulk_response ( $str )
    {
        preg_match_all ( '#\$\d+\r\n(.+)(\r\n)?#mi', $str, $matches );
        return $matches [ 1 ] ;
    }

    public function parse_bulk_response ( $str )
    {
        preg_match ( '#\$\d+\r\n(.*?)(\r\n)?#Umis', $str, $matches );
        return $matches [ 1 ] ;
    }
}
