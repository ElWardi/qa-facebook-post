<?php

/*
  Mourad M.

  File: qa-plugin/qa-facebook-post/qa-facebook-post-event.php
  Version: 1.0
  Date: 2013-08-04
  Description: Event module class for facebook post plugin
 */

class qa_facebook_post_event {

    var $event_types = array(
        'q_post', 'a_post', 'c_post',
//        'q_edit', 'a_edit', 'c_edit',
    );
    const Q_POST = 'q_post';
    const A_POST = 'a_post';
    const C_POST = 'c_post';

    function process_event($event, $userid, $handle, $cookieid, $params) {

        // get the plugin enabled option
        $plugin_enabled = (boolean) qa_opt('facebook_post_enabled');
        // check if the facebook_login_extended plugin exists as it is mandatory for the 
        // functionning of facebook post plugin
        $app_id = qa_opt('facebook_app_id');
        $app_secret = qa_opt('facebook_app_secret');
        $fb_login_enabled = !empty($app_id) && !empty($app_secret);

        if ($plugin_enabled && $fb_login_enabled && in_array($event, $this->event_types)) :

            $post_to_all = (boolean) qa_opt('facebook_post_all_enabled');

            require_once $this->directory . 'facebook.php';
            $facebook = new Facebook(array(
                'appId' => $app_id,
                'secret' => $app_secret,
                'cookie' => false,
            ));
            switch ($event) {
                case self::Q_POST:
                    $fb_post_params = array(
                        'name' => $params['title'],
                        'link' => qa_path(qa_q_request($params['postid'], $params['title']), null, qa_opt('site_url')),
                        'description' => '',
                        'caption' => 'A new question in ' . qa_opt('site_title')
                    );
                    break;
                case self::A_POST:
                    $fb_post_params = array(
                        'name' => $params['parent']['title'],
                        'link' => qa_path(qa_q_request($params['parent']['postid'], $params['parent']['title']), null, qa_opt('site_url')),
                        'description' => '',
                        'caption' => 'A new answer in ' . qa_opt('site_title')
                    );
                    break;
                case self::C_POST:
                    $fb_post_params = array(
                        'name' => $params['question']['title'],
                        'link' => qa_path(qa_q_request($params['question']['postid'], $params['question']['title']), null, qa_opt('site_url')),
                        'description' => '',
                        'caption' => 'A new comment in ' . qa_opt('site_title')
                    );
                    break;

                default:
                    $fb_post_params = array(
                        'name' => qa_opt('site_title'),
                        'link' => qa_opt('site_url'),
                        'description' => '',
                        'caption' => 'A new activity in ' . qa_opt('site_title')
                    );
                    break;
            }


            if ($post_to_all) {
                $users = $this->retrieve_facebook_users();


                foreach ($users as $user) :
                    try {
                        $facebook->setAccessToken($user['access_token']);
                        $ret = $facebook->api('/me/feed', 'POST', $fb_post_params);
                    } catch (FacebookApiException $e) {
                        error_log($e->getMessage());
                    }
                endforeach;
            } else {
                $user = $this->get_user_profile($userid);

                try {
                    $facebook->setAccessToken($user['access_token']);
                    $ret = $facebook->api('/me/feed', 'POST', $fb_post_params);
                } catch (FacebookApiException $e) {
                    error_log($e->getMessage());
                }
            }

            $post_to_page = (boolean) qa_opt('facebook_post_page_enabled');
            if ($post_to_page) :

                $page_id = $this->get_facebook_pageid(qa_opt('facebook_post_page_link'), $facebook);
                $access_token = qa_opt('facebook_post_page_token');

                if (!empty($page_id) && !empty($access_token)) :
                    $facebook->setAccessToken($access_token);
                    $accounts = $facebook->api('/me/accounts');

                    $page_token = NULL;
                    foreach ($accounts['data'] as $account) :
                        if ($account['id'] == $page_id):
                            $page = $account;
                            $page_token = $account['access_token'];
                            break;
                        endif;
                    endforeach;


                    try {
                        $facebook->setAccessToken($page_token);
                        $ret = $facebook->api('/me/feed', 'POST', $fb_post_params);
                    } catch (FacebookApiException $e) {
                        error_log($e->getMessage());
                    }
                endif;
            endif;

        endif;
    }

    function admin_form() {
        $saved = false;

        if (qa_clicked('facebook_post_save_button')) {
            qa_opt('facebook_post_enabled', (int) qa_post_text('facebook_post_enabled_field'));
            qa_opt('facebook_post_all_enabled', (int) qa_post_text('facebook_post_all_enabled_field'));
            qa_opt('facebook_post_page_enabled', (int) qa_post_text('facebook_post_page_enabled_field'));
            qa_opt('facebook_post_page_link', qa_post_text('facebook_post_page_link_field'));
            qa_opt('facebook_post_page_token', qa_post_text('facebook_post_page_token_field'));
            $saved = true;
        }

        return array(
            'ok' => $saved ? 'Facebook Wall Post saved' : null,
            'fields' => array(
                array(
                    'label' => 'Enable Facebook wall post',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('facebook_post_enabled'),
                    'tags' => 'NAME="facebook_post_enabled_field" ID="facebook_post_enabled_field"',
                ),
                array(
                    'label' => 'Allow Facebook to post about new activities on all user\'s wall',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('facebook_post_all_enabled'),
                    'tags' => 'NAME="facebook_post_all_enabled_field" ID="facebook_post_all_enabled_field"',
                ),
                array(
                    'label' => 'Allow Facebook to post about new activities on page\'s wall',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('facebook_post_page_enabled'),
                    'tags' => 'NAME="facebook_post_page_enabled_field" ID="facebook_post_page_enabled_field"',
                ),
                array(
                    'label' => 'Facebook Page link',
                    'type' => 'text',
                    'value' => qa_opt('facebook_post_page_link'),
                    'tags' => 'NAME="facebook_post_page_link_field" ID="facebook_post_page_link_field"',
                ),
                array(
                    'label' => 'Facebook access token to post to page wall*',
                    'type' => 'text',
                    'value' => qa_opt('facebook_post_page_token'),
                    'tags' => 'NAME="facebook_post_page_token_field" ID="facebook_post_page_token_field"',
                )
            ),
            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags' => 'NAME="facebook_post_save_button"',
                ),
            ),
        );
    }

    function retrieve_facebook_users() {
        $query = "SELECT u.userid, u.sessionsource, (" .
                "SELECT p1.content " .
                "FROM qa_userprofile p1 " .
                "WHERE p1.userid = u.userid " .
                "AND p1.title = 'fbid'" .
                ") AS fbid, (" .
                "SELECT p2.content " .
                "FROM qa_userprofile p2 " .
                "WHERE p2.userid = u.userid " .
                "AND p2.title = 'access_token'" .
                ") AS access_token " .
                "FROM qa_users u " .
                "WHERE sessionsource = ('facebook')";
        $result = qa_db_query_raw($query);
        return qa_db_read_all_assoc($result);
    }

    function get_user_profile($userid) {
        return qa_db_single_select(qa_db_user_profile_selectspec($userid, true));
    }

    function get_facebook_pageid($url, Facebook $fb) {
        $url = strtok($url, '?');
        $pieces = explode('/', $url); // divides the string in pieces where '/' is found
        $id = end($pieces); //takes the last piece
        if (!is_numeric($id)):
            if (!isset($page['id']))
                return null;
            $id = $page['id'];
        endif;
        return $id;
    }

}

/*
	Omit PHP closing tag to help avoid accidental output
*/
