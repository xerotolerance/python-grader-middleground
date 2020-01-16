<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 10/12/18
 * Time: 3:34 PM
 */

function login($phase)
{
#   Checks for credentials from front-end
    $ucreds = 0;

    if (isset($_POST['request'])){
        $chk = json_decode($_POST['request'], true);
        return $chk['sessionid'];
    }
    elseif (isset($_POST['ucid']) && isset($_POST['pass'])) {
        $ucreds = array('ucid' => $_POST['ucid'], 'pass' => $_POST['pass']);
    } elseif (isset($_GET['ucid']) && isset($_GET['pass'])) {
        $ucreds = array('ucid' => $_GET['ucid'], 'pass' => $_GET['pass']);
    } else {

        echo "err: could not obtain credentials";
        die;
    }
###########################################################################
#   Forwards credentials to backend & to NJIT auth server, asynchronously

    $be_url = NULL;
    $njit_url = NULL;
    #Regular cURL sessions
    if ($phase == "alpha") {
        $be_url = "https://web.njit.edu/~btc5/cs490/alpha.php";
        $njit_url = "https://aevitepr2.njit.edu/myhousing/login.cfm";
    }
    elseif ($phase=="beta"){
        $be_url = "https://web.njit.edu/~btc5/cs490/logon.php";
    }
    else{
        echo "Invalid project phase was given.\n";
        die("logon not implemented.");
    }

    $c_backend = $be_url ? curl_init($be_url) : NULL;
    $c_njit = $njit_url ? curl_init($njit_url): NULL;

    #Config for reg. cURL sessions
    $auth_ans = array();
    $curlopt_common = array(CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_POSTFIELDS => $ucreds);
    curl_setopt_array($c_backend, $curlopt_common);

    if ($c_njit) {
        curl_setopt_array($c_njit, $curlopt_common);
        curl_setopt_array($c_njit, array(CURLOPT_HEADEROPT => 1));
        $result_njit = curl_exec($c_njit);

        if (curl_getinfo($c_njit, CURLINFO_HTTP_CODE) == 302) {
            $auth_ans["njit_auth"] = "Yes";
        } else {
            $auth_ans["njit_auth"] = "No";
        }
    }

    $result_backend = curl_exec($c_backend);

    $auth_res = json_decode($result_backend, 1);

    if (key_exists('success', $auth_res)) {
        $auth_ans['backend_auth'] = $auth_res['success'];
        $auth_ans['token'] = $auth_res["token"];
    } else {
        $auth_ans['backend_auth'] = "No";
    }

    curl_close($c_backend);
    curl_close($c_njit);

#JSON Reply
    header('Content-Type: application/json');
    if ($phase=="alpha")
        echo json_encode($auth_ans, JSON_PRETTY_PRINT);
    elseif ($phase=="beta")
        echo $result_backend;
    return key_exists("token", $auth_res) ? $auth_res["token"] : NULL;
}