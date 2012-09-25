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
 
class Launch {
    // Called by launch_machine
    public static function machine()
    {
        $api = new API();

        $templates = $api->get_template_info();

        $template_data = array();
        $keys_to_include = array('processId' => '', 'processName' => '', 'description' => '');
        // TEMPLATES ROW RESULTS
        if (!empty($templates)) {
            foreach ($templates as $item) {
                $temp = array_intersect_key($item, $keys_to_include);
                $temp['launch_params_page'] = 'launch_params/' . $temp['processId'];
                $template_data[] = $temp;
            }
        }

        return $template_data;
    }


    // used by static function params (below)
    private static function filter_input_params($script_input_params)
    {
        $script_params = array('parameterKey' => '', 'parameterDataType' => '', 'parameterDefaultValue' => '', 'description' => '');
        $filter_params = array();

        foreach ($script_input_params as $param_set) { 
            $filter_params[] = array_intersect_key($param_set, $script_params);
        }
        return $filter_params;
    }


    // Called by launch_params
    public static function params($processId)
    {
        $api = new API();

        $requiredScriptParams = array('parameterKey', 'parameterDataType', 'parameterDefaultValue', 'description');
        $results = $api->get_template_process_info($processId);
        $tasks = $results['tasks'];
        $scripts = array();
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                if (array_key_exists('type', $task) && $task['type'] == 'SCRIPT') {
                    $parentTaskId = $task['parentTaskId'];
                    $script_data = $api->get_script_info($parentTaskId);
                    $script_name = $script_data['scriptName'];
                    $params = self::filter_input_params($script_data['scriptInputParams']);
                    foreach ($params as $ndx => $p) {
                        foreach ($requiredScriptParams as $required) {
                            if (!isset($p[$required])) $params[$ndx][$required] = "No $required supplied";
                        }
                    }
                    $scripts[] = array(
                        'parent_task_id' => $parentTaskId,
                        'task_id' => $task['taskId'],
                        'name' => $script_data['scriptName'],
                        'id' => $script_data['scriptId'],
                        'version' => $script_data['version'],
                        'input_params' => $params,
                    );
                }
            }
        }

        $process_data['processName'] = $results['processName'];
        $process_data['scripts'] = $scripts;
        return $process_data;
    }


    // Called by launch_submit
    public static function set_launch($post_data)
    {
        $api = new API();

        // Collect POST data, prepare $post_data array, removing superfluous items.
        $originalProcessId = $post_data['processid'];
        $scriptId = $post_data['scriptId'];
        $version = $post_data['version'];
        $taskId = $post_data['taskId'];
        $parentTaskId = $post_data['parentTaskId'];
        $exclude = array('processid' => '', 'scriptId' => '', 'version' => '', 'scriptName' => '', 'taskId' => '', 'parentTaskId' => '');
        $post_data = array_diff_key($post_data, $exclude);

        // API request: GET template info
        $result = $api->get_template_process_info($originalProcessId);
        // Copy JSON data from server to array, to facilitate insertion of user-supplied parameters from form.
        $result['tasks'][0]['taskId'] = $scriptId;
        $result['tasks'][0]['parentTaskId'] = $scriptId;
        $result['tasks'][0]['taskParameters'] = array($post_data);

        // Once user-supplied specifications have been inserted, convert back to JSON for sending to launch request
        $result = json_encode($result);
        //API request: POST  to template launch
        $request_data = $result;
        $result = $api->post_template_launch($originalProcessId, $request_data);
        if ($result) {
            $jobdetailid = $result->jobdetailid;

            // API request:  Get job information
            $response = $api->get_job_info($jobdetailid);

            return $response;
        }
        else {
            return null;
        }
    }
}

