<?php require "../secrets.php"; ?>

<html>

  <head>

    <title>Vucket > My Account</title>

    <!-- load materialize -->
    <link rel="stylesheet" href="rscs/css/materialize.min.css">

    <!-- jquery -->
    <script src="rscs/js/jquery-2.2.4.min.js"></script>

    <!-- materialize JS -->
    <script src="rscs/js/materialize.min.js"></script>

    <!-- fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">

    <!-- local -->
    <link rel="stylesheet" href="rscs/css/vucket.css">

  </head>

  <body class="grey darken-2">

    <nav>
      <div class="nav-wrapper green white-text">
        <a href="#" class="brand-logo" style="padding-left: 15px;">Vucket</a>
        <ul id="nav-mobile" class="right hide-on-med-and-down">
          <li><a class="active" href="account.php">Account</a></li>
          <li><a href="apps.php">Apps</a></li>
        </ul>
      </div>
    </nav>

    <div class="container">

      <div class="row">
        <div class="col s12">
          <ul class="tabs grey darken-3">
            <li class="tab col s3"><a href="#auth">Auth</a></li>
            <li class="tab col s3"><a href="#reports">Reports</a></li>
          </ul>
        </div>

        <div id="auth" class="col s12" style="padding: 40px;">

          <div class="card grey darken-1">
        <div class="card-content white-text">
          <span class="card-title">my Vucket</span>
          <p>Manage connected apps and services using Vucket.</p>
        </div>
      </div>

          <div>

        <div class="row">
          <div class="col s12">
            <ul class="tabs grey darken-3">
              <li class="tab col s3"><a href="#github">Github</a></li>
              <li class="tab col s3"><a href="#google">Google</a></li>
            </ul>
          </div>
          <div id="github" class="col s12">
            <div class="card grey darken-1">
              <div class="card-content white-text">
                <span class="card-title">GitHub</span>
                <p>Currently not connected</p>
              </div>

              <div class="card-action">
                <a href="https://github.com/login/oauth/authorize/?client_id=<?= $github_id; ?>" class="green-text">Connect</a>
              </div>
            </div>
          </div>

          <div id="google" class="col s12">
            <div class="card grey darken-1">
              <div class="card-content white-text">
                <span class="card-title">Google</span>
                <p>Currently not connected</p>
              </div>

              <div class="card-action">
                <a href="#!" class="green-text">Connect</a>
              </div>
            </div>
          </div>

        </div>

      </div>

        </div>

        <div id="reports" class="col s12" style="padding: 40px;">
          not yet.
        </div>

      </div>

    </div>

  </body>
</html>
