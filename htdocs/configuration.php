<?php
/**
 * This file is a part of Xen Orchesrta.
 *
 * Xen Orchestra is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Xen Orchestra is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Xen Orchestra. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Xen Orchestra
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GPLv3
 **/

require_once dirname (__FILE__) . '/../libs/prepend.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Xen Orchestra</title>

	<script type="text/javascript" src="scripts/prototype.js"></script>
	<script type="text/javascript" src="scripts/windows_js_1.3/javascripts/window.js"></script>
	<script type="text/javascript" src="scripts/windows_js_1.3/javascripts/tooltip.js"></script>
	<script type="text/javascript" src="scripts/scriptaculous.js"></script>
	<script type="text/javascript" src="scripts/livepipe.js"></script>

	<script type="text/javascript" src="scripts/md5.js"></script>

	<script type="text/javascript" src="scripts/xo.js"></script>
	<script type="text/javascript" src="scripts/xorender.js"></script>
	<script type="text/javascript">
	//<![CDATA[
		document.observe('dom:loaded', function ()
		{
			init_static();
		});
	//]]>
	</script>

	<link rel="stylesheet" media="screen" type="text/css" href="styles/stylewhite.css" />
	<link rel="stylesheet" media="screen" type="text/css" href="styles/alphacube.css" />
	<link rel="stylesheet" media="screen" type="text/css" href="styles/spread.css" />
	<link rel="stylesheet" media="screen" type="text/css" href="styles/default.css" />
</head>

<body>
	<h1>
		<a href=".">XenOrchestra</a>
	</h1>

	<ul class="menu1">
		<li><a href="index.php"><b><img src="img/house.png" alt=""/>Home</b></a></li>
		<li><a href="index.php"><b><img src="img/server.png" alt=""/>servers</b></a></li>
		<li><a href="configuration.php"><b><img src="img/conf.png" alt=""/>Configuration</b></a></li>
		<li><a href="index.php"><b><img src="img/vm.png" alt=""/>VM management</b></a></li>
		<li><a href="users.php"><b><img src="img/user.png" alt=""/>User management</b></a></li>
	<div id="login"></div></ul>
	<div id="main"
	<h3>Available API method on Dom0's (debug purpose)</h3>
<?php
foreach (Model::get_dom0s() as $dom0)
{
	$dom0_array = $dom0->get_supported_methods();
	echo '<h4>'.$dom0->id.'</h4>';
	echo '<p>';
	foreach ($dom0_array as $dom0) {
		echo $dom0.'  &nbsp|&nbsp  ';
	}
	echo '</p>';
}
?>
</div>
