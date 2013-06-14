<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_imagesUpload($path, $name, $file, $fileExtension = false)
{
	$tempImage = $path . '/__temp'; // this is a temp file

	@chmod($path, 0777);
	if (!is_writable($path))
		return false;

	if (!move_uploaded_file($file['tmp_name'], $tempImage))
		return false;

	$file['tmp_name'] = $tempImage;
	$size = @getimagesize($file['tmp_name']);
	if ($size === false || !is_array($size)) {
		@unlink($file['tmp_name']);
		return false;
	}

	$extension = array('1' => '.gif', '2' => '.jpg', '3' => '.png', '6' => '.bmp');
	if (isset($extension[$size[2]]))
		$extension = $extension[$size[2]];
	else {
		@unlink($file['tmp_name']);
		return false;
	}

	if (!empty($fileExtension) && $fileExtension != $extension) {
		@unlink($file['tmp_name']);
		return false;
	}

	$image = $path . '/' . $name . $extension;
	if (file_exists($image))
		unlink($image);
	if (!rename($file['tmp_name'], $image))
	{
		if (file_exists($file['tmp_name']))
			unlink($file['tmp_name']);

		return false;
	}

	@chmod($image, 0644);
	if (file_exists($file['tmp_name']))
		unlink($file['tmp_name']);

	return $name . $extension;
}

function activeHelper_liveHelp_imagesDelete($path, $name)
{
	$image = $path . '/' . $name;

	if (!file_exists($image) || !is_file($image))
		return false;

	unlink($image);

	return true;
}

