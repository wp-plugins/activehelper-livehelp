<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_filesDuplicate($source, $destination)
{
	if (is_dir($source))
	{
		@mkdir($destination);
		$dir = dir($source);

		while (false !== ($file = $dir->read()))
		{
			if ($file == '.' || $file == '..' )
				continue;

			$path = $source . '/' . $file;

			if (is_dir($path))
				activeHelper_liveHelp_filesDuplicate($path, $destination . '/' . $file);
			else
				copy($path, $destination . '/' . $file);
		}

		$dir->close();
	}
	else
		copy($source, $destination);
}

function activeHelper_liveHelp_filesDelete($source)
{
	if (is_file($source))
		return @unlink($source);

	if(!$dh = @opendir($source))
		return;

	while (false !== ($obj = readdir($dh)))
	{
		if($obj == '.' || $obj == '..')
			continue;

		if (!@unlink($source . '/' . $obj))
			activeHelper_liveHelp_filesDelete($source . '/' . $obj);
	}

	closedir($dh);
	@rmdir($source);
}

function activeHelper_liveHelp_filesZip($source, $destination)
{
	if (extension_loaded('zip') === true)
	{
		if (file_exists($source) === true)
		{
			$zip = new ZipArchive();

			if ($zip->open($destination, ZIPARCHIVE::CREATE) === true)
			{
				$source = realpath($source);

				if (is_dir($source) === true)
				{
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

					$baseSource = basename($source) . '/';
					$zip->addEmptyDir($baseSource);

					foreach ($files as $file)
					{
						$file = realpath($file);

						if (is_dir($file) === true)
							$zip->addEmptyDir($baseSource . substr(str_replace($source, '', $file . '/'), 1));
						else if (is_file($file) === true)
							$zip->addFromString($baseSource . substr(str_replace($source, '', $file), 1), file_get_contents($file));
					}
				}
				else if (is_file($source) === true)
					$zip->addFromString(basename($source), file_get_contents($source));
			}

			return $zip->close();
		}
	}

	return false;
}

