<?php

class Utils {
	/* creates a compressed zip file */
	static function create_zip($files = array(), $destination = '', $overwrite = false) {
		// if the zip file already exists and overwrite is false, return false
		if (file_exists ( $destination )) {
			if (! $overwrite) {
				return false;
			} else {
				unlink ( $destination );
			}
		}
		// vars
		$valid_files = array ();
		// if files were passed in...
		if (is_array ( $files )) {
			// cycle through each file
			foreach ( $files as $file => $vaNome ) {
				// make sure the file exists
				if (file_exists ( $file )) {
					$valid_files [$file] = $vaNome;
				}
			}
		}
		// if we have good files...
		if (count ( $valid_files )) {
			// create the archive
			$zip = new ZipArchive ();
			if ($zip->open ( $destination, ZIPARCHIVE::CREATE ) !== true) {				
				return false;
			}			
			// add the files
			foreach ( $valid_files as $file => $vaNome ) {
				$zip->addFile ( $file, $vaNome );								
			}
			// debug
			// echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
			
			// close the zip -- done!
			$zip->close ();
			
			// check to make sure the file exists
			return file_exists ( $destination );
		} else {
			return false;
		}
	}
}

