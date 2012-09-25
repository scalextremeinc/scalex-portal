<?php

/**
 * Copyright (c) 2012, ScaleXtreme, Inc.
 * All rights reserved.
 *
 * This file is part of the ScaleXtreme Cloud Portal V1.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice, this list
 * of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this
 * list of conditions and the following disclaimer in the documentation and/or other
 * materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

define('CACHE_TIME', 600);
date_default_timezone_set('America/Los_Angeles');
// F3 framework
require_once 'lib/base.php';
// manage customer's configuration
require_once 'classes/settings.php';
$settings = new Settings();
// for API calls
require_once 'classes/rest_api.php';
require_once 'classes/launch.php';

F3::set('config', $settings->retrieve());

F3::route('GET /',
    function() {
        F3::set('content', Template::serve('ui/home.html'));
        echo Template::serve('ui/template.html');
    }
);


F3::route('GET /launch/clear', 'clear_launch');
    function clear_launch() {
        launch(true);
    }
F3::route('GET /launch', 'launch');
    function launch($clear_cache = false) {
        $template_data = Scalex_launch::machine($clear_cache);
        F3::set('template_data', $template_data);
        F3::set('content', Template::serve('ui/select_template.html'));
        echo Template::serve('ui/template.html');
    }


F3::route('GET /launch_params/@processId',
    function() {
        $processId = F3::get('PARAMS["processId"]');

        $process_data = Scalex_launch::params($processId);

        F3::set('processId', $processId);
        F3::set('processName', $process_data['processName']);
        F3::set('scripts', $process_data['scripts']);
        F3::set('has_scripts', !empty($process_data['scripts']));

        F3::set('content', Template::serve('ui/set_params.html'));
        echo Template::serve('ui/template.html');
    }
);

F3::route('POST /launch_submit',
    function() {
        $post_data = $_POST;
        $launch_results = Scalex_launch::set_launch($post_data);
        if ($launch_results) {
            F3::set('launch_results', $launch_results);
            F3::set('content', Template::serve('ui/launch_results.html'));
        }
        else {
            F3::set('content', Template::serve('ui/launch_problem.html'));
        }
        echo Template::serve('ui/template.html');
    }
);

F3::route('GET /problem',
    function() {
        F3::set('content', Template::serve('ui/launch_problem.html'));
        echo Template::serve('ui/template.html');
    }
);


// Allow the cache to be cleared for the Dashboard page.
F3::route('GET /dashboard/clear', 'clear_dashboard');
    // Use this if the refresh / clear cache button was clicked.
    function clear_dashboard() {
        dashboard(true);
    }
F3::route('GET /dashboard', 'dashboard');
    function dashboard($clear_cache = false) {
        F3::set('fixkey', function($str) {
            return str_replace('-dot-', '.', $str);
        });
        include('classes/dashboard.php');
        $dash = new Dashboard($clear_cache);
        F3::set('serverCount', $dash->server_count);
        F3::set('status_count', $dash->status_count);
        F3::set('cloud_items', $dash->cloud_items);
        F3::set('servers', $dash->servers);
        F3::set('content', Template::serve('ui/dashboard.html'));
        echo Template::serve('ui/template.html');
    }


F3::route('GET /policy',
    function() {
        F3::set('remote_addr', $_SERVER['REMOTE_ADDR']);
        F3::set('when', date('D, M d Y') . ' at ' . date('H:i:s T'));
        F3::set('content', Template::serve('ui/policy.html'));
        echo Template::serve('ui/template.html');
    }
);


// Allow the cache to be cleared for the Settings page.
F3::route('GET /settings/clear', 'clear_settings');
    function clear_settings() {
        settings(true);
    }
F3::route('GET /settings', 'settings');
    function settings($clear_cache = false) {
        global $settings;
        $settings->settings_page_data($clear_cache);
        F3::set('company_logos', $settings->company_logos);
        F3::set('config_permissions', $settings->config_permissions);
        $status_buttons = Template::serve('ui/status_buttons.html');
        F3::set('status_buttons', Template::serve('ui/status_buttons.html'));
        F3::set('content', Template::serve('ui/settings.html'));
        echo Template::serve('ui/template.html');
    }


F3::route('POST /save_settings',
    function() {
        $post_data = $_POST;
        $settings = new Settings();
        $settings->save();
        F3::reroute('/settings');
    }
);

F3::route('GET /debug', 'debug.php');

F3::run();
