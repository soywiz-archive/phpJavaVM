<?php
class Jar extends ZipArchive
{
	protected $name;
	protected $size;
	
	public function __construct( $name )
	{
		$this->name = $name;
		$this->size = filesize( $name );
		$this->open( $name, 0 );
	}
	
	public function getManifest()
	{
		return fread( $this->getStream( 'META-INF/MANIFEST.MF' ), PHP_INT_MAX );
	}
	
	public function createJad()
	{
		return (
			"MIDlet-Jar-Size: {$this->size}\n" . 
			"MIDlet-Jar-URL: {$this->name}\n" .
			$this->getManifest()
		);
	}
}

/*
// Example.
$jar = new Jar( 'file.jar' );
echo $jar->createJad();
*/