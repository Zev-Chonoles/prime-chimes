<?php

  // Keep track of errors with this variable, handle them nicely instead of just exiting
  $error = "";

  // If sent with POST, should mean the user submitted the form to enter their own polynomial
  if($_SERVER['REQUEST_METHOD'] === 'POST')
  {

    // Should be set unless the user was intentionally messing with things
    if(isset($_POST["polynomial-input"]))
    {

      // Do a simple escape of special characters, hopefully no security problems
      $poly = htmlspecialchars(trim($_POST["polynomial-input"]));


      // Strip all whitespace - see http://stackoverflow.com/a/2109339
      $poly = preg_replace('/\s+/', '', $poly);


      // Append leading sign if necessary
      if($poly[0] !== "-" && $poly[0] !== "+")
      {
        $poly = "+" . $poly;
      }


      // Does it match my regex for polynomials?
      if(preg_match('/^(([+-])((0|[1-9][0-9]*)?x(\^(\{(0|[1-9][0-9]*)\}|(0|[1-9][0-9]*)))?|(0|[1-9][0-9]*)))+?$/', $poly))
      {

        // Insert dividers
        $poly = str_replace("+", "|+", $poly);
        $poly = str_replace("-", "|-", $poly);

        // Remove divider at beginning of string to avoid an empty term when exploding
        $poly = substr($poly, 1);

        // Explode along dividers to get terms of polynomial
        $terms = explode("|", $poly);



        // Make an associative array for mapping degree to coefficients
        $coeffs_temp = array();



        foreach ($terms as $term)
        {
          // Has coefficient, x, and exponent in braces
          if(preg_match('/^([+-])(0|[1-9][0-9]*)x\^\{(0|[1-9][0-9]*)\}$/', $term, $matches))
          {
            $coeff = intval($matches[1] . $matches[2]);
            $degree = intval($matches[3]);
          }

          // Has coefficient, x, and exponent not in braces
          elseif(preg_match('/^([+-])(0|[1-9][0-9]*)x\^(0|[1-9][0-9]*)$/', $term, $matches))
          {
            $coeff = intval($matches[1] . $matches[2]);
            $degree = intval($matches[3]);
          }

          // Has coefficient, x, and no exponent
          elseif(preg_match('/^([+-])(0|[1-9][0-9]*)x$/', $term, $matches))
          {
            $coeff = intval($matches[1] . $matches[2]);
            $degree = 1;
          }

          // Has no coefficient, x, and exponent in braces
          elseif(preg_match('/^([+-])x\^\{(0|[1-9][0-9]*)\}$/', $term, $matches))
          {
            $coeff = intval($matches[1] . "1");
            $degree = intval($matches[2]);
          }

          // Has no coefficient, x, and exponent not in braces
          elseif(preg_match('/^([+-])x\^(0|[1-9][0-9]*)$/', $term, $matches))
          {
            $coeff = intval($matches[1] . "1");
            $degree = intval($matches[2]);
          }

          // Has no coefficient, x, and no exponent
          elseif(preg_match('/^([+-])x$/', $term, $matches))
          {
            $coeff = intval($matches[1] . "1");
            $degree = 1;
          }

          // Has no x
          elseif(preg_match('/^([+-])(0|[1-9][0-9]*)$/', $term, $matches))
          {
            $coeff = intval($matches[1] . $matches[2]);
            $degree = 0;
          }

          // The term doesn't match any of the above regexes (this shouldn't be possible since
          // the whole polynomial matched the big regex, but just being extra careful)
          else
          {
            $error = "Error parsing polynomial";
          }

          $coeffs_temp[$degree] = isset($coeffs_temp[$degree]) ? $coeffs_temp[$degree] + $coeff : $coeff;
        }


        // This will be the full array of coefficients
        $coeffs_array = array();
        
        // Fill in any missing coefficients with 0's
        for($i = 0; $i <= max(array_keys($coeffs_temp)); $i++)
        {
          $coeffs_array[$i] = isset($coeffs_temp[$i]) ? $coeffs_temp[$i] : 0;
        }

        // Remove any leading 0's
        for($i = max(array_keys($coeffs_array)); $i > 0; $i--)
        {
          if($coeffs_array[$i] === 0)
          {
            array_pop($coeffs_array);
          }
          else 
          {
            break;
          }
        }
      }

      // If the whole polynomial doesn't match the big regex
      else
      {
        $error = "Error parsing polynomial";
      }
    }

    // If sent with POST, but the "polynomial-input" variable not set
    else 
    {
      $error = "Form submitted incorrectly";
    }
  }

  // If not sent with POST, should mean the page was requested as usual
  // Use some default set of coefficients
  if($_SERVER['REQUEST_METHOD'] !== 'POST' || $error !== "")
  {
    //date_default_timezone_set('America/Chicago');
    //$date = date("Ymd");
    //$coeffs_array = str_split($date, 1);

    $coeffs_array = array(1, -5, 6, 2, 4, 1, -3, -2, 1);
    $coeffs_array = array_reverse($coeffs_array);
  }


  // The degree of the polynomial
  $degree = count($coeffs_array) - 1;


  // Encode the coefficients in a string to be passed to Sage
  // (NOTE: this string is being used as a command line argument AND being sent
  // in a URL query string, so not using any special characters just to be safe)
  $coeffs_for_sage = "";
  for($i = $degree; $i >= 0; $i--)
  {
    if($coeffs_array[$i] >= 0)
    {
      $coeffs_for_sage .= "c" . $coeffs_array[$i];
    }
    else
    {
      $coeffs_for_sage .= "cn" . (-$coeffs_array[$i]);
    }
  }


  // Put the coefficients back together into a polynomial for MathJax
  $poly_for_mathjax = "";
  for($i = $degree; $i >= 0; $i--)
  {
    if($coeffs_array[$i] != 0)
    {
      if($degree > $i)
      {
        $poly_for_mathjax .= ($coeffs_array[$i] > 0) ? " +" : " ";
      }
      if($i > 0)
      {
        if($coeffs_array[$i] == -1)
        {
          $poly_for_mathjax .= "-";
        }
        elseif($coeffs_array[$i] != 1)
        {
          $poly_for_mathjax .= $coeffs_array[$i];
        }
        $poly_for_mathjax .= 'x';
        if($i > 1)
        {
          $poly_for_mathjax .= '^{' . $i . '}';
        }
      }
      else
      {
        $poly_for_mathjax .= $coeffs_array[$i];
      }
    }
  }
  // If every coefficient was 0, the string is still empty at this point
  if($poly_for_mathjax === "")
  {
    $poly_for_mathjax = "0";
  }

  // How long to make the polynomial input form (don't want the line to
  // shift around too much when revealing or hiding the form)
  $size_of_poly_form = floor(strlen($poly_for_mathjax) * 0.7);


  // How big to initially make the canvas (width will be changed by Javascript)
  // (NOTE: depends on function being used to determine circle radii)
  // (NOTE: extra space also varies with $degree because having the green circle too close to
  // a long line of blue circles can cause the blue circles to overlap the gray lines)
  $canvas_width = 800;
  $canvas_height = 2 * floor(30 * sqrt($degree)) // height of largest possible circle
                 +     floor(15 * sqrt($degree)) // some extra space
                 + 2 * floor(30 * sqrt(1));      // height of circle for base prime


  // Get a "unique" identifier for the current thread of this page
  $thread_id = uniqid('', true);
  $thread_id = str_replace(".", "e", $thread_id);

?><!doctype html>
<html lang="en-US">
<head prefix="og: http://ogp.me/ns#">

  <!-- Basic Information
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>Prime Chimes</title>
  <meta name="author" content="Zev Chonoles">
  <meta name="description" content="Listen to primes factoring in a number field.">
  <meta name="keywords" content="Zev Chonoles, math, prime, chimes, factorization, audiation, visualization">

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="shortcut icon" href="//zevchonoles.org/projects/prime-chimes/img/favicon.ico">

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="//zevchonoles.org/projects/prime-chimes/css/main.css">

  <!-- Font
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="//zevchonoles.org/projects/prime-chimes/css/cm-serif.css">

  <!-- Howler
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script async src="//zevchonoles.org/projects/prime-chimes/js/howler.min.js"></script>

  <!-- MathJax
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script type="text/x-mathjax-config"> MathJax.Hub.Config({ messageStyle: "none",
    "HTML-CSS": {linebreaks:{automatic: true,  width: "600px"}} }); </script>
  <script async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS_HTML"></script>

  <!-- Open Graph
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta property="og:title" content="Prime Chimes">
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://zevchonoles.org/projects/prime-chimes/">
  <meta property="og:image" content="http://zevchonoles.org/projects/prime-chimes/img/open-graph.png">
  <meta property="og:site_name" content="Zev Chonoles">
  <meta property="og:description" content="Listen to primes factoring in a number field.">

  <!-- Google Analytics
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-53874607-1', 'auto');
    ga('send', 'pageview');
  </script>

  <!-- Copyright and License
  ––––––––––––––––––––––––––––––––––––––––––––––––––
   This page is © <?php echo date("Y"); ?> Zev Chonoles and licensed under
   Creative Commons Attribution 4.0, summarized here:
      http://creativecommons.org/licenses/by/4.0/

   This page uses audio files from 
              http://listen.hatnote.com
   a project of Stephen LaPorte and Mahmoud Hashemi. This
   is permitted by their license, which I've included at 
  https://zevchonoles.org/prime-chimes/hatnote-license.txt
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

</head>

<body>

  <h1>Listen to primes factoring in a number field</h1>

  <div id="field">

    <span id="field-description">
      \(F=\mathbf{Q}(\alpha)\) where \(\alpha\) is a root of
    </span>

    <span id="polynomial-current" onclick="toggle_form();">
      \(<?php echo $poly_for_mathjax; ?>\)
    </span>

    <span id="polynomial-new">

      <form id="polynomial-input" action="" method="post">
        <input name="polynomial-input" type="text" size="<?php echo $size_of_poly_form; ?>"
          value="<?php echo $poly_for_mathjax; ?>"></input>
        <button id="polynomial-cancel" type="button" onclick="toggle_form();">Cancel</button>
        <button id="polynomial-submit" type="submit">Submit</button>
      </form>

      <span id="polynomial-hovertext">Note: Polynomials with large degree or large
      coefficients often take longer to start</span>

    </span>

  </div>

  <img id="loading-dots" src="//zevchonoles.org/projects/prime-chimes/img/loading-dots.gif">

  <canvas id="canvas" width="<?php echo $canvas_width; ?>" height="<?php echo $canvas_height; ?>"></canvas>

  <div id="buttons">
    <a id="math-button" href="#" onclick="toggle_math();">?</a><!--
 --><a id="mute-button" href="#" onclick="toggle_mute();" class="mute"></a>
  </div>

  <div id="factorizations"></div>

  <div id="factorizations-buffer"></div>

  <div id="about" style="display: none;">

    <h2>Basic Explanation</h2>

      <p>A whole number is prime when it isn't equal to any product of whole numbers other
      than itself and 1. However, if we allow some "new" whole numbers, something that was
      previously prime may now be equal to a product of others. Each diagram and chime
      represents how a prime number factors in a bigger number system.</p>

    <h2>Advanced Explanation</h2>

      <p>More technically, if \(p\) is our prime number, each blue circle represents one of the
      prime ideals dividing \(p\mathcal{O}_F\), with the circle's area representing inertial degree,
      and the shade of blue representing ramification index. Thus the amount of blue above each
      prime is always the same, \([F:\mathbf{Q}]=<?php echo $degree;?>\) times the amount of green.</p>

      <p>For each prime ideal dividing \(p\mathcal{O}_F\) a chime is played, whose pitch is
      determined by its inertial degree. Celesta or clavichord is used depending on whether that prime
      ideal is unramified or ramified, respectively. However, when \(p\mathcal{O}_F\) is itself prime,
      a "swell" is played. For a given \(F\), all but finitely many primes will be unramified.</p>

    <h2>Implementation</h2>

      <p>This page is built with PHP and Javascript, using AJAX to get new data as needed.
      The chimes are implemented with the <a target="_blank"
      href="//goldfirestudios.com/blog/104/howler.js-Modern-Web-Audio-Javascript-Library"><code>howler.js</code>
      library</a>.</p>

      <p>The actual factorizations are computed by <a target="_blank"
      href="//www.sagemath.org">Sage</a>. To factor the ideals \(p\mathcal{O}_F\), Sage must
      first compute \(\mathcal{O}_F\), which can be quite costly. Therefore my Sage script memoizes its
      computation of \(\mathcal{O}_F\) using the <a target="_blank"
       href="//docs.python.org/2/library/pickle.html"><code>pickle</code> module</a>.</p>

      <p>The source code is <a target="_blank" href="//github.com/Zev-Chonoles/prime-chimes/">available
      on Github</a>.</p>

    <h2>Acknowledgements</h2>

      <p>This page uses the audio files from <a target="_blank" href="http://listen.hatnote.com/">Listen
      to Wikipedia</a>, a project created by Stephen LaPorte and Mahmoud Hashemi. Here is
      <a target="_blank" href="//zevchonoles.org/projects/prime-chimes/hatnote-license.txt">a copy of
      their license</a>.</p>

      <p>The idea to make this "audiation" was somewhat inspired by <a target="_blank"
      href="http://zevchonoles.org/projects/prime-chimes/kato-poem.pdf">Kazuya Kato's poetry</a>
      about prime numbers.</p>

  </div>

  <script>

    // PASSING DATA FROM PHP TO JAVASCRIPT
    // ========================================================

    // Records whether there were any errors while PHP was running
    var php_error = "<?php echo $error; ?>";

    // Stores the coefficients as a string that will be sent to Sage
    var coeffs_for_sage = "<?php echo $coeffs_for_sage; ?>"

    // Stores the degree of the number field
    var degree = <?php echo $degree; ?>;

    // "Unique" identifier for the current thread of this page
    var thread_id = "<?php echo $thread_id; ?>";

  </script>

  <script src="https://zevchonoles.org/projects/prime-chimes/js/main.js?<?php echo uniqid(); ?>"></script>

</body>

</html>
