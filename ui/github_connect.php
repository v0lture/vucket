<?php

  // State message
  if(isset($_GET["state"]) && isset($_GET["msg"])) {
    $s = $_GET["state"];
    $msg = $_GET["msg"];
  } else {
    $s = "none";
  }

 ?>

<html>

  <head>

    <title>Vucket > Connect with GitHub</title>

    <!-- load materialize -->
    <link rel="stylesheet" href="rscs/css/materialize.min.css">

    <!-- jquery -->
    <script src="rscs/js/jquery-2.2.4.min.js"></script>

    <!-- materialize JS -->
    <script src="rscs/js/materialize.min.js"></script>

    <!-- fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">

  </head>

  <body class="grey darken-2">

    <nav>
      <div class="nav-wrapper green white-text">
        <a href="#" class="brand-logo" style="padding-left: 15px;">Vucket</a>
        <ul id="nav-mobile" class="right hide-on-med-and-down">
          <li><a href="account.php">Account</a></li>
          <li><a href="apps.php">Apps</a></li>
        </ul>
      </div>
    </nav>

    <div class="container">

      <?php if($s == "error"): ?>

        <div class="card grey darken-1">
          <div class="card-content white-text">
            <span class="card-title">GitHub connection failed</span>
            <p>You can try connecting GitHub to this account later or report this issue.<br />Error message: <code><?= $msg; ?></code></p>
            <br />
            <a href="#!" class="btn-flat waves-effect waves-light green-text">Retry</a>
            <a href="#!" class="btn-flat waves-effect waves-light green-text">Report</a>
          </div>
        </div>

      <?php elseif($s == "join"): ?>

        <div class="card grey darken-1">
          <div class="card-content white-text">
            <span class="card-title">Linking GitHub</span>
            <p>Choose an action:</p>
            <br />
            <ul class="collection transparent">
              <li class="collection-item transparent"><div>Add to my account <a href="#!" class="secondary-content"><i class="material-icons green-text">arrow_forward</i></a></div></li>
              <li class="collection-item transparent"><div>Cancel <a href="#!" class="secondary-content"><i class="material-icons green-text">arrow_forward</i></a></div></li>
            </ul>
          </div>
        </div>

      <?php endif; ?>

    </div>

  </body>
</html>
