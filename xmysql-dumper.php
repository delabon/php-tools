<?php

ini_set('max_execution_time', 0); // unlimited execution time
ini_set('memory_limit','1024M'); // set memory limit to 1024M

/**
 * MySql Database Dumper
 * @version 1.0.0
 * @author Sabri Taieb
 */

class MySql_Dumper{

    /** @var string */
    private $filename;

    /** @var string */
    private $path;

    /** @var string */
    private $database;

    /** @var string */
    private $user;

    /** @var string */
    private $pass;

    /** @var string */
    private $destination;

    /**
     * Constructor
     */
    function __construct( $database, $user, $pass, $destination ){
        
        if( empty( $database ) ){
            throw new \Exception("Please provide a database name");
        }

        if( empty( $user ) ){
            throw new \Exception("Please provide a database user");
        }

        if( empty( $pass ) ){
            throw new \Exception("Please provide a database pass");
        }

        $this->database = $database;
        $this->user = $user;
        $this->pass = $pass;
        $this->destination = empty($destination) ? __DIR__ : $destination;
        $this->filename = "backup-" . $this->database . "-" . date("d-m-Y") . ".sql.gz";
        $this->path = $this->destination .'/' . $this->filename; 

        $this->export();
    }

    /**
     * Export database
     *
     * @return void
     */
    function export(){

        // Set headers
        header( "Content-Type: application/octet-stream" );
        header( 'Content-Disposition: attachment; filename="' . $this->filename . '"' );

        // Export
        $cmd = "mysqldump -u {$this->user} -p{$this->pass} {$this->database} | gzip --best > {$this->path}";
        exec( $cmd );

        // Read
        readfile($this->path);
        die;
    }

}

/**
 * Exec
 */
$dumper = new MySql_Dumper( 
    $_GET['database'], 
    $_GET['user'], 
    $_GET['pass'], 
    $_GET['destination'] 
);
