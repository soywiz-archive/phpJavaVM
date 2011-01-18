<?php

abstract class DirectoryCachedBase
{
	protected $path;
	protected $filterPattern;
	protected $ignoreBeginDot;

	protected $listAll; // Array or DirectoryCachedBase
	protected $listFiltered;

	// Protected constructor.
	protected function __construct( $path, $filterPattern = null, $ignoreBeginDot = true )
	{
		$this->path           = $path;
		$this->filterPattern  = $filterPattern;
		$this->ignoreBeginDot = $ignoreBeginDot;
	}

	// Fetch files.
	private function fetchFiles()
	{
		// Not cached.
		if ( $this->listAll === null )
		{
			// FIX to solve a strange bug with an unexistent folder.
			if ( !is_dir( $this->path ) ) @mkdir( $this->path, 0777, true );

			$this->listAll = scandir( $this->path );
		}
		else if ( $this->listAll instanceof DirectoryCachedBase )
		{
			$this->listAll->fetchFiles();
		}
	}

	// Obtains the list of files inside the directory.
	protected function updateFilter()
	{
		// Not cached.
		if ( $this->listFiltered === null )
		{
			$this->fetchFiles();

			$this->listFiltered = array();
			foreach ( $this->listAll as $file )
			{
				if ( $this->passFilter( $file ) ) $this->listFiltered[] = $file;
			}
		}
		else if ( $this->listAll instanceof DirectoryCachedBase )
		{
			$this->listAll->updateFilter();
		}
	}

	protected function passFilter( $file, $recursive = false )
	{
		if ( ( $this->ignoreBeginDot ) && ( $file[0] == '.' ) ) return false;
		if ( ( $this->filterPattern !== null ) && !preg_match( $this->filterPattern, $file ) ) return false;
		if ( $recursive && ( $this->listAll instanceof DirectoryCachedBase ) ) return $this->listAll->passFilter( $file, $recursive );
		return true;
	}

	protected function & getListFiltered()
	{
		$this->updateFilter();
		return $this->listFiltered;
	}

	public function cached()
	{
		if ( $this->listAll instanceof DirectoryCachedBase ) return $this->listAll->cached();
		return is_array( $this->listAll );
	}

	// Obtains the real path of a file.
	public function realPath( $file = '' ) { return "{$this->path}/{$file}"; }

	public function clearCache( $rescan = true )
	{
		$this->listFiltered = null;
		if ( $this->listAll instanceof DirectoryCachedBase ) $this->listAll->clearCache( $rescan );
		else if ( $rescan ) $this->listAll = null;
	}

	public function & getAllRoot( $clearCache = true )
	{
		$this->updateFilter();
		$parent = &$this->listAll;
		while ( $parent instanceof DirectoryCachedBase ) $parent = &$parent->listAll;
		if ( $clearCache ) $this->clearCache( false );
		return $parent;
	}
}

class DirectoryCached extends DirectoryCachedBase implements IteratorAggregate, ArrayAccess, Countable
{
	// Obtains an instance of the class.
	static public function get( $path, $filterPattern = null, $ignoreBeginDot = true, $realPath = true )
	{
		if ( $realPath ) $path = realpath( $path );
		static $cache = array();
		$object = &$cache[$path];
		if ( !isset( $object ) ) $object = new self( $path, $filterPattern, $ignoreBeginDot );
		return $object;
	}

	// Obtains an array with all the files in the directory.
	public function toArray( ) { return $this->getListFiltered(); }

	// Obtains an iterator for this directory.
	public function getIterator() { return new ArrayIterator( $this->getListFiltered() ); }

	// ArrayAccess interface.
    public function count       (         ) { return count( $this->getListFiltered() ); }
	public function offsetExists( $offset ) { $v = &$this->getListFiltered(); return isset( $v[$offset] ); }
	public function offsetGet   ( $offset ) { $v = &$this->getListFiltered(); return $v[$offset]; }
	public function offsetUnset ( $offset ) { $v = &$this->getListFiltered(); unset( $v[$offset] ); } // Bug. Should be removed from the unfiltered list.
	public function offsetSet   ( $offset, $value ) { throw( new Exception( 'Not Implemented' ) ); }
	
	// Returns a filtered version of the directory.
	public function filter( $filter )
	{
		$new = clone $this;
		$new->listAll = $this;
		$new->listFiltered = null;
		$new->filterPattern = $filter;
		return $new;
	}

	// Returns a filtered version of the directory of files ended with a string.
	public function exact     ( $str ) { return $this->filter( '/^' . preg_quote( $str ) . '$/' ); }
	public function endsWith  ( $str ) { return $this->filter( '/'  . preg_quote( $str ) . '$/' ); }
	public function beginsWith( $str ) { return $this->filter( '/^' . preg_quote( $str ) . '/'  ); }
	public function contains  ( $str ) { return $this->filter( '/'  . preg_quote( $str ) . '/'  ); }

	// Checks the existence of a file in this directory.
	public function exists( $file ) { return in_array( $file, $this->getListFiltered() ); }

	// Obtains the modification time of a file.
	public function mtime( $file = '' ) { return filemtime( $this->realPath( $file ) ); }
	public function ctime( $file = '' ) { return filectime( $this->realPath( $file ) ); }

	

	// Removes a file from the directory.
	public function unlink( $file )
	{
		if ( !$this->exists( $file ) ) {
			trigger_error( "File '{$file}' doesn't exists with this/these filter/s.", E_USER_NOTICE );
			return false;
		}

		$result = unlink( $this->realPath( $file ) );
		if ( $result )
		{
			$listAll = &$this->getAllRoot();
			unset( $listAll[array_search( $file, $listAll )] );
		}
		return $result;
	}

	// Proxy for file_get_contents.
	public function getContents( $file, $offset = -1, $maxlen = -1 )
	{
		if ( !$this->exists( $file ) ) {
			trigger_error( "File '{$file}' doesn't exists with this/these filter/s.", E_USER_NOTICE );
			return false;
		}

		return ( $offset != -1 ) ? file_get_contents( $this->realPath( $file ), 0, null, $offset, $maxlen ) : file_get_contents( $this->realPath( $file ) );
	}

	// Proxy for file_put_contents.
	public function putContents( $file, $data )
	{
		if ( !$this->passFilter( $file ) ) {
			trigger_error( "Filter doesn't alloy the file '{$file}'", E_USER_NOTICE );
			return false;
		}

		$result = file_put_contents( $this->realPath( $file ), $data );
		if ( $result !== false )
		{
			$listAll = &$this->getAllRoot();
			$listAll[] = $file;
			$listAll = array_unique( $listAll );
		}
		return $result;
	}
}

/*
$l = DirectoryCached::get( '.' );
$ll = $l->exact( 'test.txt' );
$ll->putContents( 'test.txt', '1' );
$l->putContents( 'test2.txt', '1' );
assert( 'count($ll)==1' );
assert( '$ll->getContents("test.txt")==="1"' );
assert( '$ll->getContents("test2.txt")===false' );
var_dump($ll->getContents("test.txt"));

$ll->unlink( 'test.txt' );
assert( 'count($ll)==0' );

$l->unlink( 'test2.txt' );
print_r( $l->toArray() );
*/