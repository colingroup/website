<?php

/* (C) Websplosion LTD., 2001-2014

  IMPORTANT: This is a commercial software product
  and any kind of using it must agree to the Websplosion's license agreement.
  It can be found at http://www.chameleonsocial.com/license.doc

  This notice may not be removed from the source code. */

class Router {

    static public $includePageCompatibleWithSystem = '';
    static public $isUserPageCompatibleWithSystem = false;

    static function init(){
        global $g;
        global $p;

        $g['router'] = array(
            'load'      => 0,
            'load_core' => 0,
            'page'      => ''
        );

        if ($p == 'router.php') {
            $g['router']['load'] = 1;
            $g['router']['load_core'] = 1;
            $routerPage = isset($_GET['router_page']) ? $_GET['router_page'] : '';
            if (!$routerPage) {
                $routerPage = Router::getIncludePageCompatibleWithSystem();
            }
            $g['router']['page'] = $routerPage . '.php';
            $p = $g['router']['page'];
        }
    }

    static function getIncludePage($nameSeo = null, $checkEnabledGroup = false){
        global $g;

        if (self::$includePageCompatibleWithSystem) {
            return self::$includePageCompatibleWithSystem . '.php';
        }

        if ($nameSeo === null) {
            $nameSeo = get_param('name_seo');
        }

        if (!$nameSeo) {
            return '';
        }

        if ($checkEnabledGroup && get_param_int('group_id') && !Common::isOptionActiveTemplate('groups_social_enabled')) {
            return '';
        }

        $page = '';
        $uid = 0;
        $groupInfo = Groups::getInfoFromNameSeo($nameSeo);

        if ($groupInfo) {//Groups
            $nameSeo = User::getNameSeoFromUid($groupInfo['user_id']);
            if ($nameSeo) {

                Groups::isBan($groupInfo['group_id']);

                $uid = $groupInfo['user_id'];
                $_GET['name_seo'] = $nameSeo;
                $_GET['group_id'] = $groupInfo['group_id'];

                $_GET['view'] = $groupInfo['page'] ? 'group_page' : 'group';
                $_GET['type_group'] = $groupInfo['page'] ? 'page' : 'group';
            }
        } else {//Users
            $uid = User::getUidFromNameSeo($nameSeo);
        }

        if ($uid && $g['router']['page']) {
            $_GET['display'] = 'profile';
            $page = $g['router']['page'];
            if (!$groupInfo && $page === 'groups_social_subscribers') {
                $page = '';
            }
        }

        return $page;
    }

    /* Name SEO */
    static function getUniqueNameSeo($name)	{
        $sql = 'SELECT `name_seo`
				  FROM `groups_social`
                 WHERE `name_seo` LIKE \'' . to_sql($name, 'Plain') . '-%\'' ;
		$allNameSeo = DB::column($sql);

        $sql = 'SELECT `name_seo`
				  FROM `user`
                 WHERE `name_seo` LIKE \'' . to_sql($name, 'Plain') . '-%\'' ;
		$allNameSeoUser = DB::column($sql);

        $allNameSeo = array_merge($allNameSeo, $allNameSeoUser);
        $nameSeo = self::getIndexNameSeo($name, $allNameSeo);
        return $nameSeo;
    }

    static function getIndexNameSeo($name, $allNameSeo)	{
        $i = 0;
        do {
            $i++;
            $nameSeo = "{$name}-{$i}";
        } while(in_array($nameSeo, $allNameSeo) && $i < 1000000);
        return $nameSeo;
    }

    static function prepareNameSeo($name){
        return mb_strtolower(str_replace(array(' ', '?', '*', '|', '>', '<'), '_', $name), 'utf-8');
    }

    static function getNameSeo($name, $id = 0, $type = 'group', $getUnique = true) {
        $name = self::prepareNameSeo($name);
        $sql = 'SELECT `group_id` FROM `groups_social` WHERE `name_seo` = ' . to_sql($name);
        if ($id && $type == 'group') {
            $sql .= ' AND `group_id` != ' . to_sql($id);
        }
        $isNameExists = DB::result($sql, 0, DB_MAX_INDEX);
        if (!$isNameExists) {
            $sql = 'SELECT `user_id` FROM `user` WHERE `name_seo` = ' . to_sql($name);
            if ($id && $type == 'user') {
                $sql .= ' AND `user_id` != ' . to_sql($id);
            }
            $isNameExists = DB::result($sql, 0, DB_MAX_INDEX);
        }
        $nameSeo = '';
        if ($isNameExists) {
            if ($getUnique) {
                $nameSeo = self::getUniqueNameSeo($name);
            }
        } else {
            $nameSeo = $name;
        }
        return $nameSeo;
    }
    /* Name SEO */

    static function checkParamInt($param) {
        return strval($param) === strval(intval($param));
    }

    static function checkParamDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') == $date;
    }

    static function getCheckParam($param) {
        $checkParam = 'Int';
        if ($param == 'date') {
            $checkParam = 'Date';
        }
        return $checkParam;
    }

    static function getIncludePageCompatibleWithSystem($nameSeo = null) {
        if ($nameSeo === null) {
            $nameSeo = get_param('name_seo');
        }

        $includePage = 'search_results';
        if (!$nameSeo) {
            return $includePage;
        }

        $nameSeoParam = $nameSeo;
        $nameSeoPart = array();
        if (mb_strpos($nameSeo, '/', 0, 'UTF-8') !== false) {
            $nameSeoPart = explode('/', $nameSeo);
            $nameSeoParam = $nameSeoPart[0];
        }

        $groupInfo = Groups::getInfoFromNameSeo($nameSeoParam);
        if ($groupInfo) {
            $uid = $groupInfo['user_id'];
        } else {
            $uid = User::getUidFromNameSeo($nameSeoParam);
        }

        if ($uid) {
            $_GET['name_seo'] = $nameSeoParam;
        } else {
            $_GET['name_seo'] = '';
        }

        $countSeoPart = count($nameSeoPart);

        $nameSeoParamFromCheck = $nameSeoParam;
        $pagesSysytem = self::getPagesCompatibleWithSystem();
        $isPageSysytem = isset($pagesSysytem[$nameSeoParamFromCheck]);
        $lavelUrl = 1;
        if (!$isPageSysytem && $uid && $countSeoPart > 1) {
            $nameSeoParamFromCheck = $nameSeoPart[1];
            $isPageSysytem = isset($pagesSysytem[$nameSeoParamFromCheck]);
            $lavelUrl = 2;
        }

        if ($isPageSysytem) {
            $pagesSysytemData = $pagesSysytem[$nameSeoParamFromCheck];
            $isArrayPagesSysytemData = is_array($pagesSysytemData);

            if ($isArrayPagesSysytemData) {
                $includePage = $pagesSysytemData['page'];
                if (isset($pagesSysytemData['params']) && $pagesSysytemData['params']) {
                    foreach ($pagesSysytemData['params'] as $key => $value) {
                        $_GET[$key] = $value;
                    }
                }
            } else {
                $includePage = $pagesSysytemData;
            }
            if (!$uid) {
                self::$includePageCompatibleWithSystem = $includePage;
            }

            if ($countSeoPart > 1){
                $baseSeoUrl = $countSeoPart - 1;
                if ($baseSeoUrl > 0) {
                    $_GET['base_seo_url'] = $baseSeoUrl;//$lavelUrl
                }

                $paramSecondary = 'page';
                if ($isArrayPagesSysytemData && isset($pagesSysytemData['param_secondary'])) {
                    $paramSecondary = $pagesSysytemData['param_secondary'];
                }
                $paramValue = false;

                if ($paramSecondary == 'date') {
                    if (isset($nameSeoPart[$lavelUrl])) {
                        if (self::checkParamDate($nameSeoPart[$lavelUrl])) {
                            $paramValue = $nameSeoPart[$lavelUrl];
                        }
                    }
                } elseif (isset($nameSeoPart[$lavelUrl]) && self::checkParamInt($nameSeoPart[$lavelUrl])) {
                    $paramValue = intval($nameSeoPart[$lavelUrl]);
                } elseif ($uid) {
                    if (count($nameSeoPart) == 3) {
                        if (self::checkParamInt($nameSeoPart[2])) {
                            $paramValue = intval($nameSeoPart[2]);
                            $_GET['base_seo_url'] = 2;
                        } elseif ($isArrayPagesSysytemData && isset($pagesSysytemData['page_secondary'])) {//Blog post
                            if ($nameSeoPart[2]) {
                                $includePage = $pagesSysytemData['page_secondary'];
                                $paramSecondary = $pagesSysytemData['page_param_secondary'];
                                $paramValue = $nameSeoPart[2];
                                $_GET['base_seo_url'] = 2;
                            }
                        }
                    }
                }
                if ($paramValue !== false) {
                    $_GET[$paramSecondary] = $paramValue ? $paramValue : 1;
                }
            }
        }

        return $includePage;
    }

    static function getPagesCompatibleWithSystem($isGetKeys = false) {
        global $p;

        if ($p != 'router.php' && !$isGetKeys) {
            return array();
        }

        $pages = array(
            'about'                 => 'about',
            'contact'               => 'contact',
            'games'                 => 'games',
            'messages'              => 'messages',
            'page'                  => 'page',
            'audiochat'             => 'audiochat',
            'videochat'             => 'videochat',
            'email_not_confirmed'   => 'email_not_confirmed',
            'my_friends'            => 'my_friends',
            'friends_list'          => 'my_friends',
            'private_photo_access'  => 'my_friends',
            'mutual_attractions'    => 'mutual_attractions',
            'users_viewed_me'       => 'users_viewed_me',
            'join'                  => 'join',
            'join2'                 => 'join2',
            'forget_password'       => 'forget_password',
            'index'                 => 'index',
            'profile_view'          => 'profile_view',
            'login'                 => array('page' => 'join', 'params' => array('cmd' => 'please_login')),

            'terms'                 => array('page' => 'info', 'params' => array('page' => 'term_cond')),
            'privacy_policy'        => array('page' => 'info', 'params' => array('page' => 'priv_policy')),

            'users_rated_me'        => 'users_rated_me',
            'increase_popularity'   => 'increase_popularity',
            'profile_settings'      => 'profile_settings',
            'mail_whos_interest'    => 'mail_whos_interest',
            'wall'                  => 'wall',
            'general_chat'          => 'general_chat',
            'moderator'             => 'moderator',
            'upgrade'               => 'upgrade',
            'user_block_list'       => 'user_block_list',
            'search_results'        => 'search_results',
            'encounters'            => array('page' => 'search_results', 'params' => array('display' => 'encounters')),
            'hot_or_not'            => array('page' => 'search_results', 'params' => array('display' => 'encounters')),
            'rate_people'           => array('page' => 'search_results', 'params' => array('display' => 'rate_people')),
            'favorite_list'         => 'favorite_list',
            'group_add'             => 'group_add',
            'page_add'              => array('page' => 'group_add', 'params' => array('view' => 'group_page')),
            'blogs_add'             => 'blogs_add',
            'blog_edit'             => array('page' => 'blogs_add', 'param_secondary' => 'blog_id'),
            'blogs'                 => array('page' => 'blogs_list', 'page_secondary' => 'blogs_post', 'page_param_secondary' => 'blog_seo'),
            'calendar'              => array('page' => 'calendar', 'param_secondary' => 'date'),
            'task_create'           => array('page' => 'calendar_task_create', 'param_secondary' => 'date'),
            'task_edit'             => array('page' => 'calendar_task_edit', 'param_secondary' => 'event_id'),

			'photos'				=> 'photos_list',
			'vids'					=> 'vids_list',

            'blogs_post_liked'         => array('page' => 'search_results', 'params' => array('show' => 'blogs_post_liked'), 'param_secondary' => 'blog_id'),
            'blogs_post_liked_comment' => array('page' => 'search_results', 'params' => array('show' => 'blogs_post_liked_comment'), 'param_secondary' => 'comment_id'),
            'wall_liked'               => array('page' => 'search_results', 'params' => array('show' => 'wall_liked'), 'param_secondary' => 'wall_item_id'),
            'wall_liked_comment'       => array('page' => 'search_results', 'params' => array('show' => 'wall_liked_comment'), 'param_secondary' => 'comment_id'),
            'video_liked'              => array('page' => 'search_results', 'params' => array('show' => 'video_liked'), 'param_secondary' => 'video_id'),
            'video_liked_comment'      => array('page' => 'search_results', 'params' => array('show' => 'video_liked_comment'), 'param_secondary' => 'comment_id'),
            'photo_liked'              => array('page' => 'search_results', 'params' => array('show' => 'photo_liked'), 'param_secondary' => 'photo_id'),
            'photo_liked_comment'      => array('page' => 'search_results', 'params' => array('show' => 'photo_liked_comment'), 'param_secondary' => 'comment_id'),

            'city'                     => array('page' => 'city', 'params' => array('place' => 'city')),
            'street_chat'              => array('page' => 'city', 'params' => array('place' => 'street_chat')),
            '3d_labyrinth'             => array('page' => 'city', 'params' => array('place' => '3d_labyrinth')),
            '3d_tic_tac_toe'           => array('page' => 'city', 'params' => array('place' => '3d_tic_tac_toe')),
            '3d_connect_four'          => array('page' => 'city', 'params' => array('place' => '3d_connect_four')),
            '3d_chess'                 => array('page' => 'city', 'params' => array('place' => '3d_chess')),
            '3d_giant_checkers'        => array('page' => 'city', 'params' => array('place' => '3d_giant_checkers')),
            '3d_sea_battle'            => array('page' => 'city', 'params' => array('place' => '3d_sea_battle')),
            '3d_reversi'               => array('page' => 'city', 'params' => array('place' => '3d_reversi')),
            '3d_hoverboard_racing'     => array('page' => 'city', 'params' => array('place' => '3d_hoverboard_racing')),
            '3d_space_racing'          => array('page' => 'city', 'params' => array('place' => '3d_space_racing')),
            '3d_space_labyrinth'       => array('page' => 'city', 'params' => array('place' => '3d_space_labyrinth')),
            '3d_building_room'         => array('page' => 'city', 'params' => array('place' => '3d_building_room')),
            '3d_virtual_office'        => array('page' => 'city', 'params' => array('place' => '3d_virtual_office')),

            'wall_shared'              => array('page' => 'search_results', 'params' => array('show' => 'wall_shared'), 'param_secondary' => 'wall_shared_item_id'),
            'mutual_likes'             => array('page' => 'mutual_attractions'),
            'who_likes_you'            => array('page' => 'mutual_attractions', 'params' => array('cmd' => 'who_likes_you')),
            'whom_you_like'            => array('page' => 'mutual_attractions', 'params' => array('cmd' => 'whom_you_like')),

            'social_network_info'      => array('page' => 'info', 'params' => array('page' => 'social_network_info')),

            'groups'             => array('page' => 'groups_list', 'page_secondary' => 'groups_list', 'page_param_secondary' => 'name_seo'),
            'pages'              => array('page' => 'groups_list', 'page_secondary' => 'groups_list', 'page_param_secondary' => 'name_seo', 'params' => array('view' => 'group_page')),


        );

        return $pages;
    }

}