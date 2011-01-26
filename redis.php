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
 */
class redis_cli
{ 
    private $handle = false;
    private $commands = array ();
   
    public function __construct ( $host = '127.0.0.1', $port = 6379 )
    {
        $this -> handle = fsockopen ( $host, $port, $errno, $errstr );

        if ( !$this -> handle )
        { 
            return array ( $errno, $errstr );
        }
    }

    public function __destruct ()
    {
        fclose ( $this -> handle );
    }

    public function commands (  )
    {
        return $this -> commands;
    }

    public function cmd ()
    {
        if ( !$this -> handle )
        { 
            return false;
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
        return $this -> exec ();
    }

    public function get ( $line = false )
    {
        $command = $this -> exec ();

        if ( $command )
        { 
            $return = trim ( fread ( $this -> handle, 4096 ) );
            $return = explode ( "\r\n", $return );

            if ( $line && isset ( $return [ $line - 1 ] ) )
            { 
                $return = $return [ $line - 1 ];
            }
            else
            { 
                $return = end ( $return );
            }

            $return = trim ( $return, "\r\n " );

            return $return === '$-1' ? null : $return;
        }
    }

    private function exec ()
    {
        if ( !$this -> handle )
        { 
            return false;
        }

        if ( sizeof ( $this -> commands ) < 1 )
        { 
            return null;
        }

        $command = implode ( "\r\n", $this -> commands ) ."\r\n";
        fwrite ( $this -> handle, $command );

        $this -> commands = array ();
        return $command;
    }
}
