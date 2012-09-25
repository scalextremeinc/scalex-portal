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

define('ICON_DIR', 'ui/assets/icons/');
class Dashboard {
    private $_api;

    public function __construct()
    {
        $this->_api = new API();
        // NODES

        $nodeResult = $this->_api->get_nodes_info();
        $result = $nodeResult;
        $serverCount = count($result);

        $servers = array();
        $status_count = array('online' => 0, 'offline' => 0);
        if (!empty($result)) {
            foreach ($result as $item){
                if (!empty($item)) {
                    foreach ($item as $key => $val){
                        if ($key == 'nodeName') {
                            $key = str_replace('.', '-dot-', $val);
                            $servers[$key] = $this->_get_server_icon($val);
                        }
                        elseif ($key == 'status') {
                            $status_count[$val]++;
                        }
                    }
                }
            }
        }

        // CLOUDS

        $cloudResult = $this->_api->get_cloud_info();
        $result = $cloudResult;

        $cloud_items = array();
        if (!empty($result)) {
            foreach ($result as $key => $val) {
                if ($key == 'data') {
                    if (!empty($val[$key]) && is_array($val[$key])) {
                        foreach ($val[$key] as $temp) {
                            $icon = ICON_DIR . 'icon_' . $temp['code'] . '.png';
                            if (!file_exists($icon)) {
                                $icon = ICON_DIR . 'missing.png';
                            }
                            $costResult = $this->_api->get_cloud_spend_info($temp['provider_id']);
                            $cloud_temp = array(
                                'icon' => $icon,
                                'name' => $temp['name'],
                                'cost' => $costResult['data'],
                            );
                            $cloud_items[] = $cloud_temp;
                        }
                    }
                }
            }
        }
        $this->server_count = $serverCount;
        $this->status_count = $status_count;
        $this->cloud_items = $cloud_items;
        $this->servers = $servers;
    }


    private function _get_server_icon($val)
    {
        // ICON RULES
        $server_icon = '';
        if (false !== strpos($val, 'zur')) { $server_icon = "icon_azure.png"; }
        if (false !== strpos($val, 'ento') || false !== strpos($val, 'entO') || false !== strpos($val, 'cen') || false !== strpos($val, 'Cen')) { $server_icon = "icon_centos.png"; }
        if (false !== strpos($val, 'bian')) { $server_icon = "icon_debian.png"; }
        if (false !== strpos($val, 'edor')) { $server_icon = "icon_fedora.png"; }
        if (false !== strpos($val, 'hat') || false !== strpos($val, 'Hat')) { $server_icon = "icon_redhat.png"; }
        if (false !== strpos($val, 'bunt')) { $server_icon = "icon_ubuntu.png"; }
        if (false !== strpos($val, 'Windows') || false !== strpos($val, 'ndow') || false !== strpos($val, 'win') || false !== strpos($val, 'Win') || false !== strpos($val, 'x86') || false !== strpos($val, 'R2')  ) { $server_icon = "icon_windows.png"; }
        if ($server_icon) {
            $server_icon = ICON_DIR . $server_icon;
        }
        if (!file_exists($server_icon)) {
            $server_icon = ICON_DIR . 'missing.png';
        }
        return $server_icon;
    }

}
