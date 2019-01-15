<?php
if($_POST) {
    $data = $_POST;
    $call_id    = $data['call_id'];
    $direction  = $data['call_direction'];
    $hook_event = $data['hook_event'];
    $event = explode('_', $hook_event);
    $event = $event[1];

    file_put_contents("/tmp/$call_id" . "_$direction" . "_$event" . ".log", json_encode($data));
}
