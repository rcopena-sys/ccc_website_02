<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $remaining_days = $diff->d % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $parts = [];
    
    // Add weeks to the parts array if there are any
    if ($weeks > 0) {
        $parts[] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    }

    // Handle days, hours, minutes, seconds
    foreach ($string as $k => $v) {
        if ($k === 'd') {
            $value = $remaining_days;
        } else {
            $value = $diff->$k;
        }
        if ($value > 0) {
            $parts[] = $value . ' ' . $v . ($value > 1 ? 's' : '');
        }
    }

    return $parts ? implode(', ', $parts) . ' ago' : 'just now';
}
