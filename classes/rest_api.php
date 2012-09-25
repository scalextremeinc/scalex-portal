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
 
class API {
    private $_base;
    private $_http;
    private $_config;
    private $_cache_time;
    // used by most API calls for authentication
    private $_auth_token_fragment;

    public function __construct($clear_cache = false)
    {
        global $settings;

        include('httpz.php');
        $http = new httpz();
        $this->_http = new httpz();
        $this->_http->enableTest();

        $this->_config = $settings->retrieve();
        $this->_cache_time = $clear_cache ? 0 : CACHE_TIME;

        $this->_base = 'https://'.$this->_config['domain'].'/v0';
        $this->_auth_token_fragment = 'access_token=' . $this->_auth();
    }

    /*
     * This group of private methods perform the authorization necessary to obtain the
     * authorization token used throughout the API.
     */
    private function _auth()
    {
        $company_info = $this->_get_company_info();

        $role_info = $this->_get_roles_info($company_info);

        $token_info = $this->_get_oauth_token($company_info, $role_info);

        return $token_info;
    }

    private function _get_company_info()
    {
        $authUrl = $this->_base."/companies?client_id=".$this->_config['client_id'];
        $authResult =  $this->_http->get($authUrl);
        $result = json_decode($authResult, true);
        for ($i = 0, $j = sizeof($result); $i < $j; $i++) {
            if ($result[$i]['name'] == $this->_config['company_name']) {
               return $result[$i];
            }
        }
        return null;
    }

    private function _get_roles_info($company_info)
    {
        $rolesUrl = $this->_base.'/roles?client_id='.$this->_config['client_id'].'&company_id='.$company_info['companyId'];
        $rolesResult = $this->_http->get($rolesUrl, $this->_cache_time);
        $result = json_decode($rolesResult, true);

        $role_info = '';
        if (is_array($result)) {
            $roleIndex = array_search($this->_config['role'],$result);
            $role_info = $result[$roleIndex];
        }
        return $role_info;
    }

    private function _get_oauth_token($company_info, $role_info)
    {
        $grantFragment = "grant_type=client_credentials&scope=".$role_info.",".$company_info['companyId'];

        $tokenUrl = $this->_base."/oauth/token?".$grantFragment;
        $authHeaderName = "Authorization";
        $authHeaderValue = "Basic ".base64_encode($this->_config['client_id'].":".$this->_config['client_secret']);
        $this->_http->setHeader($authHeaderName, $authHeaderValue);
        $authResult = $this->_http->post($tokenUrl);
        $result = json_decode($authResult, true);
        $authToken = $result['value'];
        return $authToken;
    }
    /*
     * End of methods for acquiring authorization token.
     */



    /*
     * API calls for template information
     */
    private function _get_template_info($processId = null)
    {
        if ($processId) {
            $templatesUrl = $this->_base . "/templates/$processId?" . $this->_auth_token_fragment;
        }
        else {
            $templatesUrl = $this->_base."/templates?".$this->_auth_token_fragment;
        }
        $result = $this->_http->get($templatesUrl, $this->_cache_time);
        return $result;
    }

    public function get_template_info($fmt = 'json')
    {
        $result = $this->_get_template_info();
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public function get_template_process_info($processId, $fmt = 'json')
    {
        $result = $this->_get_template_info($processId);
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public function post_template_launch($processId, $json_data, $fmt = 'json')
    {
        $url = $this->_base . "/templates/$processId/launch?" . $this->_auth_token_fragment;
        $this->_http->setHeader("Content-Type", "application/json");
        $result = $this->_http->post($url, $json_data);
        if ($fmt == 'json') {
            $result = json_decode($result);
        }
        return $result;
    }


    /*
     * API calls for script information
     */
    private function _get_script_info($id = null)
    {
        if ($id) {
            $scriptInfoURL = $this->_base . "/scripts/$id?" . $this->_auth_token_fragment;
        }
        else {
            $scriptInfoURL = $this->_base . "/scripts?" . $this->_auth_token_fragment;
        }
        $result = $this->_http->get($scriptInfoURL, $this->_cache_time);
        return $result;
    }


    public function get_script_info($id, $fmt = 'json')
    {
        $result = $this->_get_script_info($id);
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }


    public function get_scripts_info($fmt = 'json')
    {
        $result = $this->_get_script_info();
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }


    /*
     * API calls for job info
     */
    private function _get_job_info($id, $call = null)
    {
        if ($call) {
            $jobsInfoURL = $this->_base . "/jobs/$id/$call" . '?' . $this->_auth_token_fragment;
        }
        else {
            $jobsInfoURL = $this->_base . "/jobs/$id?" . $this->_auth_token_fragment;
        }
        $result = $this->_http->get($jobsInfoURL, $this->_cache_time);
        return $result;
    }

    public function get_job_info($id, $fmt = 'json')
    {
        $result = $this->_get_job_info($id);
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public function get_job_runinfo($id, $fmt = 'json')
    {
        $result = $this->_get_job_info($id, 'runinfo');
        if ($fmt === 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }


    /*
     * API calls for nodes info
     */
    public function get_nodes_info($fmt = 'json')
    {
        $url = $this->_base . '/nodes?' . $this->_auth_token_fragment;
        $result = $this->_http->get($url, $this->_cache_time);
        if ($fmt == 'json') {
            $result = json_decode($result,true);
        }
        return $result;
    }


    /*
     * Cloud info
     */
    public function get_cloud_info($fmt = 'json')
    {
        $url = 'https://' . $this->_config['domain'] . '/scalex/acl/getcloudproviders?' . $this->_auth_token_fragment . '&companyId=' . $this->_config['company_id'] . '&role=Admin&user=12234';
        $result = $this->_http->get($url, $this->_cache_time);
        if ($fmt == 'json') {
            $result = json_decode($result,true);
        }
        return $result;
    }


    public function get_cloud_spend_info($provider_acct_id, $fmt = 'json')
    {
        $url = "https://".$this->_config['domain']."/scalex/budget/usagebyprovider?" . $this->_auth_token_fragment . "&organizationId=".$this->_config['company_id']."&provideraccountid=".$provider_acct_id."&budgetedorganizationid=".$this->_config['company_id']."&periodtype=MONTHLY&budgetlevel=ORGANIZATION";
        $result = $this->_http->get($url, $this->_cache_time);
        if ($fmt == 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }

}
