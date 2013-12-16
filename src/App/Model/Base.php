<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
	
	/**
	* Retrieve ETag for single resource
	*
	* @return string ETag for resource
	*/
	public function getEtag($regen=false)
	{
		if ( $this->exists && ($this->etag === false || $regen === true)  )
    	{
    		$this->etag = $this->generateEtag();
    	}

    	return $this->etag;
	}

	/**
	* Generate ETag for single resource
	*
	* @return string ETag, using md5
	*/
	private function generateEtag()
	{
		$etag = $this->getTable() . $this->getKey();

		if ( $this->usesTimestamps() )
		{
			$datetime = $this->updated_at;

			if ( $datetime instanceof \DateTime )
			{
				$datetime = $this->fromDateTime($datetime);
			}

			$etag .= $datetime;

		}

    	return md5( $etag );
	}

}