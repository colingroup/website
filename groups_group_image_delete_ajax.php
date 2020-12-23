<?php
/* (C) Websplosion LTD., 2001-2014

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/main_start.php");
require_once("./_include/current/groups/tools.php");

function do_action()
{
	global $g_user;
	
	$image_id = intval(get_param('image_id'));
	
	if($image_id)
	{
        CGroupsTools::delete_group_image($image_id);
        echo 'ok';
        die();
	}
}

do_action();

include("./_include/core/main_close.php");

?>