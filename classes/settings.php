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
 
class Settings {
    private $_config;

    public function __construct()
    {
        $this->_config = $this->retrieve();
    }

    public function settings_page_data($clear_cache = false, $debug_mode = false)
    {
        $api = new API($clear_cache);
        $scriptsResult = $nodesResult = $cloudsResult = $templatesResult = $costResult = '';
        $scriptsResult = $api->get_scripts_info('raw');
        $nodesResult = $api->get_nodes_info('raw');
        $cloudsResult = $api->get_cloud_info('raw');
        $templatesResult = $api->get_template_info('raw');
        $provider_acct_id = array_key_exists('provider_acct_id', $this->_config) ? $this->_config['provider_acct_id'] : '';
        $costResult = $api->get_cloud_spend_info('', 'raw');

        $debug = array();
        if ($debug_mode) {
            if ($scriptsResult == '[]' || (strlen($scriptsResult) == 0) || (strpos($scriptsResult,'FAILURE')) ) {
                $debug['scripts'] = '<a href="debug#scripts" class="btn btn-danger">Scripts</a>';
            } else {
                $debug['scripts'] = '<a href="debug#scripts" class="btn btn-success">Scripts</a>';
            }
            if ($nodesResult == '[]' || (strlen($nodesResult) == 0)  || (strpos($nodesResult,'FAILURE'))) {
                $debug['nodes'] = '<a href="debug#nodes"  class="btn btn-danger">Nodes</a>';
            } else {
                $debug['nodes'] = '<a href="debug#nodes"  class="btn btn-success">Nodes</a>';
            }
            if ($cloudsResult == '[]' || (strlen($cloudsResult) == 0) || (strpos($cloudsResult,'FAILURE')) ) {
                $debug['clouds'] = '<a href="debug#clouds"  class="btn btn-danger">Clouds</a>';
            } else {
                $debug['clouds'] = '<a href="debug#clouds"  class="btn btn-success">Clouds</a>';
            }
            if ($templatesResult == '[]' || (strlen($templatesResult) == 0) || (strpos($templatesResult,'FAILURE')) ) {
                $debug['templates'] = '<a href="debug#templates" class="btn btn-danger">Templates</a>';
            } else {
                $debug['templates'] = '<a href="debug#templates"  class="btn btn-success">Templates</a>';
            }
            if ($costResult == '[]' || (strlen($costResult) == 0) || (strpos($costResult,'FAILURE')) ) {
                $debug['cost'] = '<a href="debug#cost" class="btn btn-danger">Cost</a>';
            } else {
                $debug['cost'] = '<a href="debug#cost" class="btn btn-success">Cost</a>';
            }
        }
        $this->debug = $debug;

        $config_permissions = $this->_file_perms('data/config.txt');

        // TURN THE ARRAY RESULT INTO A DROPDOWN SELECT
        $company_logos = $this->_get_logo_files( "ui/assets/logos/", "*.*");

        $this->config_permissions = $config_permissions;
        $this->company_logos = $company_logos;
    }

    private function _get_logo_files($path = '.', $mask = '*')
    {
        $dir = @ dir($path);

        $logos = array();
        while (($file = $dir->read()) !== false) {
            if ($file != '.' && $file != '..' && fnmatch($mask, $file))
                $logos[] = $file;
        }
        $dir->close();
        return ($logos);
    }

    private function _file_perms($file, $octal = false)
    {
        if(!file_exists($file)) return false;
        $perms = fileperms($file);
        $cut = $octal ? 2 : 3;
        return substr(decoct($perms), $cut);
    }

    public function retrieve()
    {
        $data = file_get_contents('data/config.txt');
        $settings = explode("\n", $data);
        $config = array();
        foreach ($settings as $setting) {
            $pair = explode(':', $setting, 2);
            if (sizeof($pair) == 2) {
                $key = $pair[0];
                $value = $pair[1];
                $config[$key] = $value;
            }
        }
        return $config;
    }

    public function save()
    {
        foreach ($_POST as $key => $value) {
            $data[] = "$key:$value";
        }
        $output = implode("\n", $data);

        $fp = fopen("data/config.txt", "w");
        fwrite($fp, $output);
        fclose($fp);
    }


}

