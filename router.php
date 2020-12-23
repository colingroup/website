<?php
/* (C) Websplosion LTD., 2001-2014

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */
include("./_include/core/main_start.php");

$g['router']['load_core'] = 0;

$page = Router::getIncludePage(null, true);

if ($page) {
    include($page);
} else {
    pageNotFound();
}

include_once("./_include/core/main_close.php");