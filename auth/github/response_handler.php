<?php

  // Will handle tokens received from GitHub and redirect to appropriate locations.

  // you supply this file with your github ID and secret!
  require_once "../../secrets.php";

  $url = "https://github.com/login/oauth/access_token";
  $data = Array('client_id' => $github_id, 'client_secret' => $github_secret, 'code' => $_GET["code"]);

  $options = Array(
    'http' => Array(
      'header' => Array("Content-type: application/x-www-form-urlencoded", "Accept: application/json"),
      'method' => "POST",
      'content' => http_build_query($data),
    )
  );

  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);

  if($result === false) {
    // internal error
    header("Location: https://vucket.v0lture.com/ui/report.php?app=Vucket&code=VI001");
  }

  $decoded = json_decode($result, true);
  if(array_key_exists('error', $decoded)) {
    // github did not like it
    header("Location: https://vucket.v0lture.com/ui/github_connect.php?state=error&msg=".$decoded["error_description"]);
  } elseif(array_key_exists('access_token', $decoded)) {
    // github gave us a code, let's continue

    $token = $decoded["access_token"];

    $url = "https://api.github.com/user?access_token=".$token;

    if(!function_exists('curl_init')) {

      // we ain't got curl, redirect back.
      header("Location: https://vucket.v0lture.com/ui/report.php?app=Vucket&code=VI002");
    } else {

      // init curl
      $c = curl_init();
      curl_setopt($c, CURLOPT_URL, $url);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($c, CURLOPT_USERAGENT, "Vucket");

      $resp = curl_exec($c);
      curl_close($c);
      $json = json_decode($resp, true);

      if(array_key_exists("message", $json)) {
        // failure message
        header("Location: https://vucket.v0lture.com/ui/github_connect.php?state=error&msg=".$json["message"]);
      } else {
        // valid af

        if($mysql->connect_error == null) {
          // mysql didnt connect
          header("Location: https://vucket.v0lture.com/ui/report.php?app=Vucket&code=VI003");
        } else {
          // mysql did connect

        }
      }
    }
  }


?>
