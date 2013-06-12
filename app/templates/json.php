<?php
if (!isset($status)) 
    $status = 'OK';

echo json_encode(array(
    'status' => $status, 
    'result' => $result
        ));