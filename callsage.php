<?php
  // Sage was complaining that the environment variable $HOME was not set
  // (I guess because PHP is calling it, not me?) Anyway this seems to fix it
  putenv("HOME=/CENSORED");

  // Escaping HTML characters
  $coeffs = htmlspecialchars($_GET["coeffs"]);
  $start = htmlspecialchars($_GET["start"]);
  $thread_id = htmlspecialchars($_GET["thread_id"]);

  // Do they match my regexes?
  if(preg_match('/^(cn?(0|[1-9][0-9]*))+?$/', $coeffs) &&
     preg_match('/^(0|[1-9][0-9]*)$/', $start) &&
     preg_match('/^[a-f0-9]+$/', $thread_id))
  {
    // timeout does exactly what it says
    $cmd = "timeout 20s ./script.sage " . $coeffs . " " . $start . " " . $thread_id;
    echo shell_exec($cmd);
  }
  else
  {
    exit("Error occurred in callsage.php");
  }

?>