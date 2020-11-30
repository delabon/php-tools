<?php

ini_set('max_execution_time', 0); // unlimited execution time
ini_set('memory_limit','1024M'); // set memory limit to 1024M

/**
 * Extending ZipArchive
 */
class ExtendedZip extends ZipArchive {

    // Member function to add a whole file system subtree to the archive
    public function addTree($dirname, $localname = '') {
        if ($localname)
            $this->addEmptyDir($localname);
        $this->_addTree($dirname, $localname);
    }

    // Internal function, to recurse
    protected function _addTree($dirname, $localname, $excluded = [ '.', '..' ]){

        $dir = opendir($dirname);
        
        while ($filename = readdir($dir)) {
            
            // Excluded ?
            if( in_array( $filename, $excluded ) )
                continue;

            // Proceed according to type
            $path = $dirname . '/' . $filename;
            $localpath = $localname ? ($localname . '/' . $filename) : $filename;

            if (is_dir($path)) {
                // Directory: add & recurse
                $this->addEmptyDir($localpath);
                $this->_addTree($path, $localpath);
            }
            else if (is_file($path)) {
                // File: just add
                $this->addFile($path, $localpath);
            }
        }

        closedir($dir);
    }

    // Helper function
    public static function zipTree($dirname, $zipFilename, $flags = 0, $localname = '') {
        $zip = new self();
        $zip->open($zipFilename, $flags);
        $zip->addTree($dirname, $localname);
        $zip->close();

        return $zip;
    }
}

/**
 * MySql Database Dumper
 * @version 1.0.0
 * @author Sabri Taieb
 */

class Zip_Recursively{

    /** @var string */
    private $filename;

    /** @var string */
    private $path;

    /** @var string */
    private $source;

    /** @var string */
    private $destination;

    /**
     * Constructor
     */
    function __construct( $source, $destination = '' ){
        
        if( empty( $source ) ){
            throw new \Exception("Please provide a source");
        }

        $this->source = $source;

        if( ! preg_match("/^\//", $this->source ) ){
            $this->source = __DIR__ . '/' . $this->source;
        }

        if( ! file_exists( $this->source ) ){
            throw new \Exception("Source folder or file does not exist.");
        }

        $this->destination = empty($destination) ? __DIR__ : $destination;

        if( ! preg_match("/^\//", $this->destination ) ){
            $this->destination = __DIR__ . '/' . $this->destination;
        }

        if( ! file_exists( $this->destination ) ){
            throw new \Exception("Destination folder does not exist.");
        }

        $this->filename = "backup-" . strtolower(basename($this->source)) . "-" . date("d-m-Y") . ".sql.gz";
        $this->path = $this->destination .'/' . $this->filename; 

        $this->zip_now();
    }

    /**
     * Zip source
     *
     * @return void
     */
    function zip_now(){

        $tmp_folder = '/tmp/phpziptmp/';
        $tmp_source = $tmp_folder . basename( $this->source );

        // Create a temp folder
        if( ! file_exists( $tmp_folder ) ){
            mkdir( $tmp_folder );
            mkdir( $tmp_source );
        }

        // Copy folder and content to tmp folder
        $this->copy_folder_recursively( $this->source, $tmp_source );

        // Delete old zip folder
        if( file_exists( $this->path ) ){
            unlink( $this->path );
        }
    
        // Zip folder
        ExtendedZip::zipTree( $tmp_folder, $this->path , ZipArchive::CREATE);

        // Delete tmp folder
        $this->delete_folder_recursively( $tmp_folder );

        echo "\nSuccess: " . $this->path;
    }

    /**
     * Copy folder recursively
     *
     * @param string $src
     * @param string $dst
     * @return void
     */
    private function copy_folder_recursively( $src, $dst ){ 
        
        $dir = opendir($src);
        @mkdir($dst); 
        
        while(false !== ( $file = readdir($dir)) ) { 
            if( ( $file != '.' ) && ( $file != '..' ) ){ 
                if ( is_dir($src . '/' . $file) ) { 
                    $this->copy_folder_recursively( $src . '/' . $file,$dst . '/' . $file ); 
                } 
                else { 
                    copy($src . '/' . $file,$dst . '/' . $file); 
                } 
            } 
        }

        closedir($dir); 
    }

    /**
     * Delete folder recursively
     *
     * @param string  $dir 
     * @return void
     */
    private function delete_folder_recursively( $dir ) {
        
        if( ! is_dir( $dir ) ){
            throw new InvalidArgumentException(" $dir  must be a directory");
        }

        if( empty($dir) ){
            throw new InvalidArgumentException("Please provide a directory path");
        }
        
        $files = array_diff(scandir($dir), array('.', '..')); 

        foreach ($files as $file) { 
            ( is_dir("$dir/$file") ) ? $this->delete_folder_recursively( "$dir/$file" ) : unlink( "$dir/$file" ); 
        }

        return rmdir($dir);
    }

}

/**
 * Exec
 */
$dumper = new Zip_Recursively( 
    $_GET['source'], 
    $_GET['destination']
);
