<?php
/**
 * Package: File Analyzer
 * This simple tool can be used to detect and remove the UTF-8 BOM.
 * Inspired by Allesandro Calo's recursivedirscan.php from www.smfportal.de
 *
 * @package File Analyzer
 * @author Thorsten Eurich
 * @copyright 2012 Thorsten Eurich
 * @license BSD 3-clause 
 * @todo: selectable file extensions, ajax feedback, proper error handling, documentation
 *
 * @version 0.1
 */
 
@set_time_limit(600);

$worker = new Worker();

if (method_exists($worker, 'performAction' . $_GET['step']))
	call_user_func(array($worker, 'performAction' . $_GET['step']));

class Worker
{
	private $counter;
	private $template;
	private $files;
	private $fixed;

	public function __construct()
	{
		$this->files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__FILE__)));
		$this->template = new template();
		$this->template->header();
	}

	public function __destruct()
	{
		$this->template->footer();
	}

	public function performAction0()
	{
		$this->template->step0();
	}

	public function performAction1()
	{
		$file_types = array('txt', 'php', 'js', 'html', 'css');
		$this->counter = 0;

		foreach($this->files as $name => $object)
		{
			if (!in_array(substr($name, -3), $file_types))
				continue;

			if (remove_utf8bom($name) == true)
				$this->fixed->$name = $name;

			++$this->counter;
		}
		$this->template->step1((int) $this->counter, $this->fixed);
	}
}

class template
{
	public function header()
	{
		echo '
<!DOCTYPE html>
<html>
	<head>
		<title>File Analyzer</title>
		<style type="text/css">
			body {background-color: #cbd9e7; margin: 0px; padding: 0px;}
			#header {background-color: #809ab3; padding: 22px 4% 12px 4%; color: #fff; text-shadow: 0 0 8px #333; font-size: xx-large;	border-bottom: 1px solid #fff; height: 40px;}
			#main {padding: 20px 30px;	background-color: #fff;	border-radius: 5px;	margin: 7px; border: 1px solid #abadb3;}
			body, td {color: #000;	font-size: small; font-family: arial;}
			h1 {margin: 0; padding: 0;	font-size: 24pt;}
			h2 {margin: 0;	position: relative;	top: 15px; border-radius: 7px; left: 10px;	padding: 5px; display: inline; background-color: #fff; font-size: 10pt;	color: #809ab3;	font-weight: bold;}
			.content {border-radius: 3px;	background-color: #eee;	color: #444; margin: 1ex 0;	padding: 1.2ex;	border: 1px solid #abadb3;}
		</style>
	</head>
	<body>
		<div id="header">
			<h1>File Analyzer</h1>
		</div>
		<div id="main">';
	}

	public function footer()
	{
		echo '
		</div>
	</body>
</html>';
	}

	public function step0()
	{
		echo '
		<h2>Welcome!</h2>
		<div class="content">
			<form action="', $_SERVER['PHP_SELF'], '?step=1" method="post">
				<div>This tool is used to check your source files containing the UTF-8 Byte Order Mark (BOM).<br /><br/ >It can be used to diagnose and <b>repair</b> files. Simply tick the "Start scan" button and wait a few seconds in order to see the result.</div>
				<div style="margin-top: 10px"><input id="submit_button" name="submit_button" type="submit" value="Start scan" class="submit" /></div>
			</form>
		</div>';
	}

	public function step1($counter, $fixed)
	{
		echo '
		<h2>Analysing done! Below are the results of the scan</h2>
		<div class="content">
			<p>', $counter, ' Files have been scanned.</p>';

		if (!empty($fixed))
		{
			echo '
			<p>The following files have been fixed:</p>
			<ul>';

			foreach ($fixed as $obj => $name)
				echo '<li>' . $name . '</li>';

			echo'
			</ul>';
		}
		else
			echo '
			<p>Congratulations, all files are OK.</p>';

		echo '
		</div>';
	}
}

function remove_utf8bom($filename) 
{
	$size = filesize($filename);

	if ($size < 3) 
		return false;

	if ($fh = fopen($filename, 'r+b'))
	{
		if (bin2hex(fread($fh, 3)) == 'efbbbf') 
		{
			if ($size == 3 && ftruncate($fh, 0)) 
			{
				fclose($fh);
				return false;
			}
			else 
				if ($buffer = fread($fh, $size))
				{
					if (ftruncate($fh, strlen($buffer)) && rewind($fh))
					{
						if(fwrite($fh, $buffer))
						{
							fclose($fh);
							return true;
						}
					}
				}
		}
		else 
		{ 
			fclose($fh);
			return false;
		}
	}
    return false;
}
