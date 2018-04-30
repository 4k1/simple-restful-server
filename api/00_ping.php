<?php

    namespace ping;

    class MethodHandler {
    
        // GET /ping
        function post_config() { return ["requireAppkey" => false, "requireUserAuth" => false]; }
        function get($api, $request, &$response) {
            // Regist pong entry
            $response["pong"] = $api;
            return;
        }
        
    }
