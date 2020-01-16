<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 9/11/18
 * Time: 2:54 PM
 */
include_once ("alpha.php");
include_once ("beta.php");
$sessionid = array_key_exists("sessionid", $_POST) ? $_POST["sessionid"] : login("beta");
if (!$sessionid)
    die("Could not authenticate session token.");

if (array_key_exists("request", $_POST))
    $result = forward_to_frontend($sessionid);
echo $result;