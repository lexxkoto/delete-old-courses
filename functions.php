<?php

require_once('rest.php');

$colors = Array(
    'off'       => "\e[0m",
    'red'       => "\e[0;31m",
    'green'     => "\e[0;32m",
    'yellow'    => "\e[0;33m",
    'blue'      => "\e[0;34m",
);

function out($text, $symbol='-', $color='blue') {
    global $colors;
    echo date('H:i:s').' '.$colors[$color].'['.$symbol.']'.$colors['off'].' '.$text.PHP_EOL;
}

function sendToMoodle($function, $data, $debug=false) {
    global $config;
        
    $rest = new MoodleRest(
        $config['site'].'/webservice/rest/server.php',
        $config['token']
    );
    
    if($debug) {
        $rest->setDebug();
    }
    
    $response = $rest->request($function, $data, MoodleRest::METHOD_POST);
    
    if(isset($response['exception'])) {
        out('Web service error: '.$response['message'], '!', 'red');
        if($config['fail']) {
            die();
        }
    }
    
    return $response;
}
