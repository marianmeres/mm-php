<?php
/**
 * Human-Readable-Seconds
 * 
 * adjusted from: http://www.weberdev.com/get_example-4769.html
 * 
 * @param type $seconds 
 */
function humanReadableSeconds($seconds, $returnAsArray = false) {
    //$periods = array("sec", "min", "hour", "day", "week", "month", "year", "decade");
    //$lengths = array("60",  "60",  "24",   "7",   "4.35", "12",    "10");
    // we don't want "decade"
    $periods = array("sec", "min", "hour", "day", "week", "month", "year");
    $lengths = array("60",  "60",  "24",   "7",   "4.35", "12");
    
    $difference = $seconds;
    
    /*for($j = 0; $difference >= $lengths[$j]; $j++) {
        $difference /= $lengths[$j];
    }*/
    $j = 0;
    while (isset($lengths[$j]) && $difference >= $lengths[$j]) {
        $difference /= $lengths[$j++];
    }
    $difference = round($difference);
    
    // add plural
    if($difference != 1) {
        $periods[$j] .= "s";
    }
    
    $out = array(
        'amount' => $difference,
        'period' => $periods[$j],
        'word'   => $difference . " " . $periods[$j],
    );

    if (!$returnAsArray) {
        return $out['word'];
    }
    
    return $out;
}

/*
function RelativeTime($timestamp){
    $difference = time() - $timestamp;
    $periods = array("sec", "min", "hour", "day", "week", "month", "years", "decade");
    $lengths = array("60",  "60",  "24",   "7",   "4.35", "12",    "10");

    if ($difference > 0) { // this was in the past
        $ending = "ago";
    } else { // this was in the future
        $difference = -$difference;
        $ending = "to go";
    }       
    for($j = 0; $difference >= $lengths[$j]; $j++) {
        $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if($difference != 1) {
        $periods[$j].= "s";
    }
    $text = "$difference $periods[$j] $ending";
    return $text;
} */