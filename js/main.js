
// MATH TOGGLE
// ========================================================

// Initial logical state
var show_math = false;

// Get button
var math_button = document.getElementById("math-button");

function toggle_math()
{
  if(show_math)
  {
    // Change logical state
    show_math = false;

    // Change page
    var current_math = document.getElementsByClassName("current-show")[0];
    current_math.className = "current";

    // Change button
    math_button.innerHTML = "+";
  }
  else
  {
    // Change logical state
    show_math = true;

    // Change page
    var current_math = document.getElementsByClassName("current")[0];
    current_math.className = "current-show";

    // Change button
    math_button.innerHTML = "&#8722;"; // HTML entity for minus sign
  }

  // Should prevent "#" from being appended to the URL in the address bar
  // from http://javascript.info/tutorial/default-browser-action
  event.preventDefault ? event.preventDefault() : (event.returnValue=false);
}  




// MUTE TOGGLE
// ========================================================

// Initial logical state
var mute = false;

// Get button
var mute_button = document.getElementById("mute-button");

function toggle_mute()
{
  if(mute)
  {
    // Change logical state
    mute = false;

    // Change page
    Howler.unmute();

    // Change button
    mute_button.className = "mute";
  }
  else
  {
    // Change logical state
    mute = true;

    // Change page
    Howler.mute();

    // Change button
    mute_button.className = "unmute";
  }

  // Should prevent "#" from being appended to the URL in the address bar
  // from http://javascript.info/tutorial/default-browser-action
  event.preventDefault ? event.preventDefault() : (event.returnValue=false);
}




// POLYNOMIAL FORM TOGGLE
// ========================================================

// Initial logical state
var show_form = false;

// Get relevant elements
var polynomial_new = document.getElementById("polynomial-new");
var polynomial_current = document.getElementById("polynomial-current");

function toggle_form()
{
  if(show_form)
  {
    // Change logical state
    show_form = false;

    // Change page
    polynomial_new.style.display = "none";
    polynomial_current.style.display = "inline-block";
  }
  else
  {
    // Change logical state
    show_form = true;

    // Change page
    polynomial_new.style.display = "inline-block";
    polynomial_current.style.display = "none";
  }
}




// AUDIO PREPARATION
// ========================================================

// Loading the audio files into Howler once in advance, then playing those copies
// when needed, seems to run more stably than loading and playing repeatedly

var celestas = [];
for (var i = 1; i <= 27; i++)
{
  celestas.push(new Howl(
    {
      urls: ['http://zevchonoles.org/prime-chimes/aud/celestas/celesta' + i + '.ogg',
             'http://zevchonoles.org/prime-chimes/aud/celestas/celesta' + i + '.mp3']
    }))
}

var clavs = [];
for (var i = 1; i <= 27; i++)
{
  clavs.push(new Howl(
    {
      urls: ['http://zevchonoles.org/prime-chimes/aud/clavs/clav' + i + '.ogg',
             'http://zevchonoles.org/prime-chimes/aud/clavs/clav' + i + '.mp3']
    }))
}

var swells = [];
for (var i = 1; i <= 3; i++)
{
  swells.push(new Howl(
    {
      urls: ['http://zevchonoles.org/prime-chimes/aud/swells/swell' + i + '.ogg',
             'http://zevchonoles.org/prime-chimes/aud/swells/swell' + i + '.mp3']
    }))
}




// VISUAL PREPARATION
// ========================================================

// Gets the canvas
var canvas = document.getElementById("canvas");
var context = canvas.getContext("2d");

// Sets the font and alignment for the digits
context.font = "16pt 'Computer Modern Serif'";
context.textAlign = "center";

// Write something (invisibly) in the canvas so that the Computer
// Modern font is loaded by the time we actually want to start
context.fillStyle = '#ffffff';
context.fillText("foo", 0, 0);

// The height of the canvas is determined with PHP and stays fixed, but
// the width of the canvas starts at 800px and then Javascript will resize
// it with each new prime to the width of the browser window.
//
// Setting a canvas dimension attribute will also clear the canvas and its
// current properties (see http://stackoverflow.com/a/5517885). So when resizing
// and clearing the canvas, we have to put the font size and alignment back in.
function resize_and_clear_canvas()
{
  canvas.width = document.body.clientWidth;
  context.font = "16pt 'Computer Modern Serif'";
  context.textAlign = "center";
}

// Defines the radius (in px) for a circle representing a prime of inertial degree f
function radius(f)
{
  return Math.floor(30 * Math.sqrt(f));
}

// Defines the horizontal spacing (in px) between circles
var space = 20;




// AJAX PREPARATION
// ========================================================

// Takes coefficients of polynomial and the index of the prime to start at,
// sends that information to the Sage script via XMLHttpRequest, then places
// the output in whichever element is currently named "factorizations-buffer"
function getOutput(coeffs, start)
{
  var xhr = new XMLHttpRequest();
  var buffer = document.getElementById("factorizations-buffer");

  // Defines what to do when the readyState of the request changes
  // readyState starts at 0 (unsent), gets to 4 (done) when request is finished
  xhr.onreadystatechange = function ()
  {
    if(xhr.readyState === 4)
    {
      // The HTTP status code 200 means "OK"
      if(xhr.status === 200)
      {
        // As soon as request is complete, fills in the buffer with the response
        buffer.innerHTML = xhr.responseText;

        // Tells MathJax to start typesetting the responseText
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, buffer]);

        // Stuff to do if this is the first AJAX call
        if(start === "0")
        {
          // If there were any errors when PHP was running,
          // warn the user about what happened
          if(php_error !== "")
          {
            alert(php_error + ", using default polynomial");
          }
        
          // Turn off loading dots
          document.getElementById("loading-dots").style.display = "none";
        
          // Make relevant things visible
          document.getElementById("field").style.display = "block";
          document.getElementById("canvas").style.display = "block";
          document.getElementById("buttons").style.display = "block";
          document.getElementById("about").style.display = "block";

          // Start drawing and chiming
          draw_and_chime(0);
        }
      }

      // A status of 0 just means the XMLHttpRequest was canceled before it could finish
      // (so no need to make an alert about it) See http://stackoverflow.com/q/3825581
      else if (xhr.status !== 0)
      {
      	alert("Oops! XMLHttpRequest returned with HTTP status code " + xhr.status);
      }
    }
  }

  // Sets the HTTP request method and the URL of the resource being requested
  xhr.open("GET", "callsage.php?coeffs=" + coeffs + "&start=" + start + "&thread_id=" + thread_id);

  // Initiates the AJAX call
  xhr.send();
}




// MAIN LOOP
// ========================================================

// Sends the first AJAX call
getOutput(coeffs_for_sage, "0");

function draw_and_chime(n)
{

  // Get information
  // ------------------------------------------------------

  // If we're starting a new batch of factorizations from Sage on this loop,
  // switch to it and clear away the old batch
  if(n % 100 == 0)
  {
    // Switch which element is the buffer
    current = document.getElementById("factorizations");
    buffered = document.getElementById("factorizations-buffer");
    current.id = "factorizations-buffer";
    buffered.id = "factorizations";

    // Clear the element that has just been made the buffer - this way, trying to get
    // information from it prematurely will produce an error, not old information
    current.innerHTML = "";
  }


  // If we're halfway through the current batch of factorizations, tell Sage to start
  // computing a new batch and put the result in the buffer when done
  if(n % 100 == 50)
  {
    getOutput(coeffs_for_sage, 100 * Math.ceil( n / 100 ));
  }


  // If the factorization info for the nth prime is ready to go, get it and
  // clear away old image. Otherwise exit the function, thereby ending the loop
  // of "draw_and_chime" calling itself
  if(document.getElementById("factorization" + n))
  {
    var Z_prime = document.getElementById("Z_prime" + n).innerHTML;
    var ramification = document.getElementById("ramification" + n).innerHTML.split("-");
    var inertia = document.getElementById("inertia" + n).innerHTML.split("-");
    var OF_prime_count = inertia.length;
    resize_and_clear_canvas();
  }
  else
  {
    document.getElementById("about").style.display = "none";
    alert("Call to Sage either took too long (>20s) or produced an error");
    return;
  }




  // Where to draw things?
  // ------------------------------------------------------

  // The radii of the circles are determined by applying "radius" to the inertial degrees of the O_F primes
  var radii = inertia.map(radius);


  // The total width of X objects with a constant space between them is the object widths + the (X-1) spaces 
  var total_width = space * (OF_prime_count - 1);
  for(var i = 0; i < OF_prime_count; i++) 
  { 
    total_width += 2 * radii[i]; 
  }


  // Consider this crude diagram:
  //
  //    |
  //    |(------##------)< - - >(------##------)< - - >   . . .
  //    |   r1      r1      s      r2      r2      s
  //
  // If the starting point on the left had x-coordinate 0, the first circle center would be at r1,
  // and each one after that would be r(i-1) + s + ri to the right of the previous center
  //
  // If instead we wanted to center the entire group of circles around 0, we should shift left
  // by half the total width of the circles and spaces
  //
  // But in fact, we want to center the entire group of circles around the center of the canvas,
  // so we should center them around 0 and then shift them right by half the width of the canvas
  var xcenters = [radii[0] - (total_width / 2) + (canvas.width / 2)];
  for(var i = 1; i < OF_prime_count; i++) 
  { 
    xcenters.push(xcenters[i-1] + radii[i-1] + space + radii[i]);
  }


  // Place the centers so they always lie on the same horizontal line every time
  // Set the distance of that line from the top of the canvas to be the largest possible radius
  var ycenters = [radius(degree)];
  for(var i = 1; i < OF_prime_count; i++)
  {
    ycenters.push(ycenters[i-1]);
  }


  // Place the circle representing the Z prime at the bottom middle of the canvas
  // It will have the radius associated with primes of inertial degree 1, as is only logical
  var Z_prime_xcenter = canvas.width / 2;
  var Z_prime_ycenter = canvas.height - radius(1);
  var Z_prime_radius = radius(1);




  // Draw things!
  // ------------------------------------------------------

  // Draw the gray lines from the circle centers
  context.strokeStyle = '#CCCCCC';
  context.lineWidth = 3;
  for(var i = 0; i < OF_prime_count; i++) 
  { 
    context.beginPath();
    context.moveTo(xcenters[i],ycenters[i]);
    context.lineTo(Z_prime_xcenter,Z_prime_ycenter);
    context.stroke();
  }


  // Draw the green circle representing the Z prime
  context.fillStyle = '#99EEBB';
  context.beginPath();
  context.arc(Z_prime_xcenter, Z_prime_ycenter, Z_prime_radius, 0, 2 * Math.PI);
  context.fill();


  // Put the value of the Z prime inside the green circle
  context.fillStyle = '#447755';
  context.fillText(Z_prime, Z_prime_xcenter, Z_prime_ycenter + 8);


  // Draw the blue circles representing each of the O_F primes
  // If a prime is ramified, make it a darker blue
  for(var i = 0; i < OF_prime_count; i++)
  { 
    switch(parseInt(ramification[i]))
    {
      case 1:
        context.fillStyle = '#99CCFF';
        break;
      case 2:
        context.fillStyle = '#3399FF';
        break;
      case 3:
        context.fillStyle = '#0066FF';
        break;
      case 4:
        context.fillStyle = '#0033CC';
        break;
      default:
        context.fillStyle = '#000099';
        break;
    }
    context.beginPath();
    context.arc(xcenters[i], ycenters[i], radii[i], 0, 2 * Math.PI);
    context.fill();
  }




  // Make noise!
  // ------------------------------------------------------

  // If Z_prime is totally inert in F, play a random "swell"
  //
  // Otherwise, play a combination of chimes, one for each O_F prime, determined by
  // their inertial degrees - unramified primes get celesta, ramified primes get clavichord
  if(inertia[0] == degree)
  {
    swells[Math.floor(Math.random() * 3)].play();
  }
  else
  {
    for(var i = 0; i < OF_prime_count; i++)
    {
      // 27 appears because there are 27 celesta chimes and 27 clav chimes
      if(degree <= 27)
      {
        var index = (inertia[i] - 1) * Math.floor(27 / degree);
      }
      else
      {
        var index = Math.floor((inertia[i] - 1) * 27 / degree);
      }

      if(ramification[i] > 1)
      {
        // Shifted up by 8 because it sounds better to me
        index = Math.min(index + 8, 26);
        clavs[index].play();
      }
      else
      {
        // Shifted up by 1 because it sounds better to me
        index = Math.min(index + 1, 26);
        celestas[index].play();
      }
    }
  }




  // Show math (if toggled on)!
  // ------------------------------------------------------

  var current_math = document.getElementById("latex" + n);
  current_math.className = "current";

  if(n % 100 > 0)
  {
   var previous_math = document.getElementById("latex" + (n-1));
   previous_math.className = "";
  }

  if(show_math)
  {
    current_math.className = "current-show";
  }




  // Wait between 2 and 5 seconds before going to next prime
  // ------------------------------------------------------

  setTimeout(function(){draw_and_chime(n+1);}, 2000 + Math.floor(3000*Math.random()));

}



