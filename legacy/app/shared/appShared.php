<?php

function isQ($q)
{
    return ($q == 1) ? 'Ja' : 'Nee';
}

function dateWithFail($timeStamp){

  if($timeStamp == ''){
    return '';
  } else{
    return date('d-m-Y', $timeStamp);
  }

}

function dillutionTextToArray($dillText) {

    if ($dillText == '') {
        $dillutionArr['0'] = '1';

    } else {
        $text = trim($dillText);
        $textAr = explode("\n", $text);
        $textAr = array_filter($textAr, 'trim');

        $dillutionArr = array();

        foreach ($textAr as $line) {
            $dInst = explode('=', $line);

            if (isset($dInst['0']) && isset($dInst['1'])) {
                $dillutionArr[$dInst['0']] = trim($dInst['1']);
            }
        }
    }

    return $dillutionArr;
}


//table mods
function researchProfileScope($clientId){
    if($clientId == 0){
        return 'Global';
    } else{
        return customerIdToName($clientId);
    }
}

//if an array comes along, you must
function flipIt($input, $keyArr){
    //flip it,
    //flip it good,
    foreach($keyArr as $key=>$val){
        $input = str_replace($key, $val, $input);
    }
    return $input;
}

//supplements writer
function neatSupplements($json){

  $arr = json_decode($json, JSON_FORCE_OBJECT);
  $arr =  checkArrayOrEmpty($arr);
  $list = '';

  foreach($arr as $suppId => $supp ){
    $list .= '<li>' . $supp['name'] . '</li>';
  }

  return $list;
}


function unicode_trim ($str) {
    return preg_replace('/^[\pZ\pC]+([\PZ\PC]*)[\pZ\pC]+$/u', '$1', $str);
}

//aliases
function getUserProfile($userId){
    return pa('profiles', 'getProfileInfo', array($userId));
}

function userIdToName($userId){
    return upa('profiles', 'getUserFullName', array($userId));
}

function projectIdtoName($projectId){
    return pa('projects', 'projectIdToName', array($projectId));
}


function customerIdToName($customerId){
    return pa('clients', 'clientIdToName', array($customerId), 0);
}

function subclientIdToName($subcustomer){
    return pa('subClients', 'subclientIdToName', array($subcustomer), 0);
}

function create_thumbnail($infile, $outfile, $maxw, $maxh, $stretch = FALSE, $force = False) {
    clearstatcache();
    if (!is_file($infile)) {
        trigger_error("Cannot open file: $infile", E_USER_WARNING);
        return FALSE;
    }
    if (is_file($outfile)) {
        trigger_error("Output file already exists: $outfile", E_USER_WARNING);
        return FALSE;
    }

    $functions = array(
        'image/png' => 'ImageCreateFromPng',
        'image/jpeg' => 'ImageCreateFromJpeg',
    );

    // Add GIF support if GD was compiled with it
    if (function_exists('ImageCreateFromGif')) {
        $functions['image/gif'] = 'ImageCreateFromGif';
    }

    $size = getimagesize($infile);

    // Check if mime type is listed above
    if (!$function = $functions[$size['mime']]) {
        trigger_error("MIME Type unsupported: {$size['mime']}", E_USER_WARNING);
        return FALSE;
    }

    // Open source image
    if (!$source_img = $function($infile)) {
        trigger_error("Unable to open source file: $infile", E_USER_WARNING);
        return FALSE;
    }

    $save_function = "image" . strtolower(substr(strrchr($size['mime'], '/'), 1));

    // Scale dimensions
    list($neww, $newh) = scale_dimensions($size[0], $size[1], $maxw, $maxh, $stretch, $force);

    if ($size['mime'] == 'image/png') {
        // Check if this PNG image is indexed
        $temp_img = imagecreatefrompng($infile);
        if (imagecolorstotal($temp_img) != 0) {
            // This is an indexed PNG
            $indexed_png = TRUE;
        } else {
            $indexed_png = FALSE;
        }
        imagedestroy($temp_img);
    }

    // Create new image resource
    if ($size['mime'] == 'image/gif' || ($size['mime'] == 'image/png' && $indexed_png)) {
        // Create indexed
        $new_img = imagecreate($neww, $newh);
        // Copy the palette
        imagepalettecopy($new_img, $source_img);

        $color_transparent = imagecolortransparent($source_img);
        if ($color_transparent >= 0) {
            // Copy transparency
            imagefill($new_img, 0, 0, $color_transparent);
            imagecolortransparent($new_img, $color_transparent);
        }
    } else {
        $new_img = imagecreatetruecolor($neww, $newh);
    }

    // Copy and resize image
    imagecopyresampled($new_img, $source_img, 0, 0, 0, 0, $neww, $newh, $size[0], $size[1]);

    // Save output file
    if ($save_function == 'imagejpeg') {
        // Change the JPEG quality here
        if (!$save_function($new_img, $outfile, 100)) {
            trigger_error("Unable to save output image", E_USER_WARNING);
            return FALSE;
        }
    } else {
        if (!$save_function($new_img, $outfile)) {
            trigger_error("Unable to save output image", E_USER_WARNING);
            return FALSE;
        }
    }

    // Cleanup
    imagedestroy($source_img);
    imagedestroy($new_img);

    return TRUE;
}

// Scales dimensions
function scale_dimensions($w, $h, $maxw, $maxh, $stretch = FALSE, $force = False) {

    if($force == True){
        return array($maxw, $maxh);
    }

    if (!$maxw && $maxh) {
        // Width is unlimited, scale by width
        $newh = $maxh;
        if ($h < $maxh && !$stretch) {
            $newh = $h;
        } else {
            $newh = $maxh;
        }
        $neww = ($w * $newh / $h);
    } elseif (!$maxh && $maxw) {
        // Scale by height
        if ($w < $maxw && !$stretch) {
            $neww = $w;
        } else {
            $neww = $maxw;
        }
        $newh = ($h * $neww / $w);
    } elseif (!$maxw && !$maxh) {
        return array($w, $h);
    } else {
        if ($w / $maxw > $h / $maxh) {
            // Scale by height
            if ($w < $maxw && !$stretch) {
                $neww = $w;
            } else {
                $neww = $maxw;
            }
            $newh = ($h * $neww / $w);
        } elseif ($w / $maxw <= $h / $maxh) {
            // Scale by width
            if ($h < $maxh && !$stretch) {
                $newh = $h;
            } else {
                $newh = $maxh;
            }
            $neww = ($w * $newh / $h);
        }
    }
    return array(round($neww), round($newh));
}

function dillutionToText($json, $onlyIdent = False,  $br = False){

    $arr = json_decode($json, True);
    $text = False;

    foreach($arr as $dillution=>$dillFactor){

        if($onlyIdent == False){
            $text .= $dillution . '=' . $dillFactor;
        } else {
            $text .= $dillution;
        }

        if($br == False){
            $text .= '&#13;&#10;';
        } else{
            $text .= '<br />';
        }
    }

    return $text;
}

function userAvatar($userId){
    $url = upa('profiles', 'getUserAvatar', array($userId));
    return $url;
}

function userAvatarUri($userId, $uri){
    $url = ALPC_BASEPATH . '/public/uploads/' . $userId . '/avatar/' . $uri;
    return $url;
}


function generateSignature($userId, $returnData = False, $lang = ''){

    $userId = $userId . $lang;

    if(!file_exists(ROOT. '/app/private/signatures/' . $userId . '.png')){
        if($returnData == True){
            return '/9j/4AAQSkZJRgABAQEAYABgAAD/4QBaRXhpZgAATU0AKgAAAAgABQMBAAUAAAABAAAASgMDAAEAAAABAAAAAFEQAAEAAAABAQAAAFERAAQAAAABAAAOw1ESAAQAAAABAAAOwwAAAAAAAYagAACxj//bAEMAAgEBAgEBAgICAgICAgIDBQMDAwMDBgQEAwUHBgcHBwYHBwgJCwkICAoIBwcKDQoKCwwMDAwHCQ4PDQwOCwwMDP/bAEMBAgICAwMDBgMDBgwIBwgMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDP/AABEIACoAJwMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJCgv/xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR8CQzYnKCCQoWFxgZGiUmJygpKjQ1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4eLj5OXm5+jp6vHy8/T19vf4+fr/xAAfAQADAQEBAQEBAQEBAAAAAAAAAQIDBAUGBwgJCgv/xAC1EQACAQIEBAMEBwUEBAABAncAAQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVictEKFiQ04SXxFxgZGiYnKCkqNTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqCg4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhEDEQA/AP38oornfix8Q/8AhVPw81TXxofiLxNJp0QaLStCsvteo6hIzBEihjJVdzMwG6R0jQZZ3RFZgm7K7Gld2R0VFeK/AP8Aba034zfGPW/hzrXgvxx8MPiFoumRa5/wj/ipNPafUNNkcxC8tprC7u7aWNZQY3Am3o2NyKGUt7VT6J9H/nZ/c00+zTW6FfVrqv8Ah/yaa7pp7BRRRQAUh6cde1LSEbhj1pO9tAPkH/gn98EPiB8WPiYv7SXxrlhsPiTqnh658G6Z4YsvD0+i2nhjTI9TlkfclzNNPPPcNDBIZWMabBGFiH3m+v64f9nb9nHwZ+yf8J7HwP4A0b+wPC+mz3VzbWX2ue68uS5uJLmZvMnd5DullkbBYgbsDAAA7intFQWy2+bbf3tt+bdw+1KT3b++2i9NEtNlsgooooAKKKKACiiigAooooA//9k=';
        } else{
            return False;
        }

    } else{
        if($returnData == True){
            return base64_encode(file_get_contents(ROOT. '/app/private/signatures/' . $userId . '.png'));
        } else{
            return ROOT. '/app/private/signatures/' . $userId . '.png';
        }
    }
}



//this will generate all the nececsairy tags and make up for any avatar
function generateAvatar($userId, $uri = False, $type = 'small', $polarize = False, $urlOnly = False ){

    //if not supplied fetch URI
    if($uri == False){
       $uri = upa('profiles', 'getUserAvatar', array($userId));
    }

    //if its empty or false
    if($uri == False || $uri == ''){
        if($type == 'small'){
            $avatarLocation = ALPC_BASEPATH . '/public/img/noavatar_male_small.jpg';
        } else{
            $avatarLocation = ALPC_BASEPATH . '/public/img/noavatar_male.jpg';
        }

    } else{
        if($type == 'small'){
            $avatarLocation = ALPC_BASEPATH . '/public/uploads/' . $userId .  '/avatar/' . $uri;
        } else{
            $avatarLocation = ALPC_BASEPATH . '/public/uploads/' . $userId .  '/photos/' . $uri;
        }

    }

    //return the needed information
    if($urlOnly == True){
        return $avatarLocation;
    }

    if($urlOnly == False){

        if($polarize == True){ $additionalClasses = 'img-polaroid'; }
        else{ $additionalClasses = False; }

        if($type == 'small'){
            $avatarHTML = '<div class="avaCrop ' . $additionalClasses . '"><img src="'. $avatarLocation . '" class="avatar-32 " /></div>';
        }

        elseif($type == 'large'){
            $avatarHTML = '<img src="' . $avatarLocation . '" class="avatar-170 ' . $additionalClasses .  '" />';
        }

        return $avatarHTML;
    }
}

function format_number_significant_figures($number, $sf, $autoAdjust = True)
{
    
    if($autoAdjust == True)
    {
        if($number < 100)
        {
            $sf = 1;
        }
    }

    // How many decimal places do we round and format to?
    // @note May be negative.
    $dp = floor($sf - log10(abs($number)));

    // Round as a regular number.
    $number = round($number, (int)$dp);

    // Leave the formatting to format_number(), but always format 0 to 0dp.
    //$nF = number_format($number, 0 == $number ? 0 : $dp, ',', '.');
    $nF = number_format($number, 0, ',', '.');

    return $nF;

}

if (!function_exists('mime_content_type')) {
    function mime_content_type($filename) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'png' => 'image/png',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'rtf' => 'application/rtf',
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );


        $extExplode = explode('.',$filename);
        $extPrior = array_pop($extExplode);
        $ext = strtolower($extPrior);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }

    }
}

function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

function checkKey($array) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
      if (!isset($array[$args[$i]])){
        return false;
      }
      $array = &$array[$args[$i]];
  }
  return true;
}

function checkKeyOrBlank($array) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
      if (!isset($array[$args[$i]])){
        return '';
      }
      $array = &$array[$args[$i]];
  }

  return $array;
}

function checkKeyOrNULL($array) {
    $args = func_get_args();
    for ($i = 1; $i < count($args); $i++) {
        if (!isset($array[$args[$i]])){
          return NULL;
        }
        $array = &$array[$args[$i]];
    }
  
    return $array;
  }

function checkKeyOrFalse($array) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
      if (!isset($array[$args[$i]])){
        return false;
      }
      $array = &$array[$args[$i]];
  }

  return $array;
}


function checkKeyOrEmpty($array) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
      if (!isset($array[$args[$i]])){
        return array();
      }
      $array = &$array[$args[$i]];
  }

  return $array;
}


function checkKeyOrTrue($array) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
      if (!isset($array[$args[$i]])){
        return True;
      }
      $array = &$array[$args[$i]];
  }

  return $array;
}

function checkArrayOrEmpty($array){

  if(is_array($array)){
    return $array;
  } else{
    return array();
  }
}

function ChiSq($x,$n) {
    if ($x>1000 || $n>1000) {
            $q=Norm((pow($x/$n,1/3)+2/(9*$n)-1)/sqrt(2/(9*$n)))/2;
            if ($x>$n)
                return $q;
            else
                return 1-$q;
        }
    $p=exp(-0.5*$x);
        if(($n%2)==1) { $p=$p*sqrt(2*$x/pi());       }
    $k=$n;
        while($k>=2) {
        $p=$p*$x/$k;
        $k=$k-2;
        }
   $t=$p;
     $a=$n;
     while($t>1e-15*$p) {
        $a=$a+2;
        $t=$t*$x/$a;
        $p=$p+$t;
        }

    return 1-$p;
}

function Norm($z) {
    $q=$z*$z;
   if (abs($z)>7)
        return (1-1/$q+3/($q*$q))*exp(-$q/2)/(abs($z)*sqrt($PiD2));
    else
        return ChiSq($q,1);
}

function mesaUnlink($file){
  if( isset($_SESSION['DEVELOPMENT']) && $_SESSION['DEVELOPMENT'] == True){
      cphp('Did not delete file, development mode on');
  } else{
      unlink($file);
  }
}

function cphp($log){
  
}

function indexAlpacaArray($array, $indexer = 'id'){
    if(!is_array($array)){
      return array();
    }

    $retArray = array();
    foreach($array as $arrayEntry){
      $retArray[$arrayEntry[$indexer]] = $arrayEntry;
    }

    return $retArray;
}

function peekIntoJSON($json, $koi){

  $array = json_decode($json, JSON_FORCE_OBJECT);
  if(is_array($array)){
    if(array_key_exists($koi, $array)){
      return $array[$koi];
    } else{
      return NULL;
    }
  }else{
    return NULL;
  }

}

function guidv4($data = null) {
    
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? openssl_random_pseudo_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function wordwrapTruncate($string,$length=100,$append="&hellip;") {
    $string = trim($string);
  
    if(strlen($string) > $length) {
      $string = wordwrap($string, $length);
      $string = explode("\n", $string, 2);
      $string = $string[0] . $append;
    }
  
    return $string;
  }

function truncate($string,$length=100,$append="...") {
    return (strlen($string) > $length) ? substr($string, 0, $length - strlen($append)) . $append : $string;
}

function stripPathForbidden($string){
    return str_replace(array('\\','/',':','*','?','"','<','>','|'),'',  $string);
}


function fileNameFilter(string $filename) : string 
{
    

    try {        
        $translit = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        return preg_replace('/[^a-zA-Z0-9\(\)\-\s\.]/', '', $translit);    
    } catch (\Throwable $th) {
        return preg_replace('/[^a-zA-Z0-9\(\)\-\s\.]/', '', $filename);
    }
    
}

function mysqldateToApplicationTimeZone($date)
{
    $date = new DateTime($date, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Amsterdam'));
    return $date->format('Y-m-d H:i:s');
}

