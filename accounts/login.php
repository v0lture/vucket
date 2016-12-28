<?php

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../secrets.php";
  global $db;

  // we handle logging in or continuing to 2FA
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);

  $resp = $auth->UP($_GET["u"], $_GET["p"]);

  echo "<pre>";
  print_r($resp);
  echo "</pre>";

 ?>

 <?php if(isset($resp["error"])): ?>
   <h2>Shucks, I couldn't log you in as <b><?= $_GET["u"]; ?></b>@<code><?= $_GET["p"]; ?></code></h2>
   <p>Error: <code><?= $resp["error"]; ?></p>
 <?php else: ?>
   <h2>Hello <b>test</b>.</h2>
   <p>I logged you in with the token <b><?= $resp["token"]; ?></b>.</p>
   <p>Has this request been logged? <b><?= $resp["logged"]; ?></b></p>
 <?php endif; ?>
