<?php
/*
Plugin Name: Time spent on blog
Plugin URI: http://iron.randombase.com
Description: This neat little widget shows the total time spent on your blog by all your users, with a precision of two seconds and the exception of people with Javascript turned off. Don't forget to activate in the widget interface!
Author: Iron
Version: 1.0
Author URI: http://iron.randombase.com
*/
function installTimeSpentPlease()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "timewaster";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name)
	{
		$sql = "CREATE TABLE `$table_name` (
		`ip` text NOT NULL,
		`lasthit` int(9) NOT NULL default '0',
		`totalhits` varchar(9) NOT NULL default ''
		)";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	 }
}
register_activation_hook(__FILE__,'installTimeSpentPlease');
function checkForAjax()
{
	if(isset($_GET['getSum']))
	{
		global $wpdb;
		$d = $wpdb->get_row("SELECT SUM(totalhits) FROM ".$wpdb->prefix."timewaster",'ARRAY_N');
		print $d[0];
		die();
	}
	elseif(isset($_GET['synChro']))
	{
		global $wpdb;
		if(@$res = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."timewaster WHERE ip = '$_SERVER[REMOTE_ADDR]'",'ARRAY_A'))
		{
			if(time() - $res['lasthit'] < 5)
			{ $sdf = time() - $res['lasthit'] + $res['totalhits'];
				mysql_query("UPDATE ".$wpdb->prefix."timewaster SET lasthit = '".time()."', totalhits = '".$sdf."', lasthit = '".time()."' WHERE ip = '$_SERVER[REMOTE_ADDR]'");
			}
			else
			{
				$res['totalhits']++;
				$wpdb->query("UPDATE ".$wpdb->prefix."timewaster SET lasthit = '".time()."', totalhits = '".$res['totalhits']."' WHERE ip = '$_SERVER[REMOTE_ADDR]'");
			}
		}
		else
		{
			$wpdb->query("INSERT INTO ".$wpdb->prefix."timewaster VALUES ('$_SERVER[REMOTE_ADDR]','".time()."','0')");
		}
		die();
	}
}
add_action('init', 'checkForAjax');
function addOurScript()
{
	global $wpdb;
	print '<script language="javascript" type="text/javascript">
	var startTime = "';
	print (@$res = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."timewaster WHERE ip = '$_SERVER[REMOTE_ADDR]'",'ARRAY_A'))? $res['totalhits'] : 0;
	print '";
	function loopz()
	{
		setTimeout("loopz()",1000);
		startTime++;
		document.getElementById(\'countz\').innerHTML = convertReadable(startTime);
	}
	function convertReadable(giefTime)
	{
		days = 0; hrs = 0; mins = 0;
		while(giefTime > 86400)
		{
			days++;
			giefTime -= 86400;
		}
		while(giefTime > 3600)
		{
			hrs++;
			giefTime -= 3600;
		}
		while(giefTime > 59)
		{
			mins++;
			giefTime -= 60;
		}
		return days+" days, "+hrs+" hours, "+mins+" minutes and "+giefTime+" seconds.";
	}
	function updateTime(){
		setTimeout("updateTime()", 2000);
		var ajaxRequest;
		try{
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			try{
				ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try{
					ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e){
					return false;
				}
			}
		}
		ajaxRequest.open("GET", "'.get_option('siteurl').'/index.php?synChro", true);
		ajaxRequest.send(null); 
	}
	function updateTotal(){
		setTimeout("updateTotal()", 10000);
		var ajaxRequest;
		try{
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			try{
				ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try{
					ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e){
					return false;
				}
			}
		}
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){
				document.getElementById(\'totalz\').innerHTML = convertReadable(ajaxRequest.responseText);
			}
		}
		ajaxRequest.open("GET", "'.get_option('siteurl').'/index.php?getSum", true);
		ajaxRequest.send(null); 
	}
	</script>';
}
add_action('wp_print_scripts','addOurScript');

function bindzor()
{
	function widget_timeonline($args)
	{
		extract($args);
		echo $before_widget; 
		echo $before_title.'Time spent on this blog'.$after_title; 
		echo '<li><b>By you</b><br /><div id="countz"></div></li>
		<li><b>By the world</b><br /><div id="totalz"></div></li><script language="javascript" type="text/javascript">updateTotal();loopz();updateTime();</script>';
		echo $after_widget; 
	}
	register_sidebar_widget('Total time on this blog', 'widget_timeonline');
}
add_action('plugins_loaded', 'bindzor');
?>