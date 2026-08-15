<?php

// each bit of gen-site.php will be unique per site

// this is for yomonsni


// using bootstrap-4.3.1
// hacked up a bit of the startbootstrap-grayscale theme v5.0.7

// yomonsni content tree:
// topleve: index, ethics, testimonials, terms
// about  -  about the site, the philosophy
// development - about personal development and growth
// lifestyle - about yomonsni as a lifestyle
// massage - all about what makes a yomonsni massage
// reflections - something of a blog
// extras - tidbits
// image-assets - images used in the site


// startbootstrap-grayscale

// modify grayscale.css

// set masthead image background
// set masthead background-attachment to fixed

// change button color

// navbar bg color to transparent on shrink
// navbar color to dddddd on shrink



// modify index.html

// relabel navbar and hack so that it moves to different pages
// remove the js scroll trigger

// add actual page content to masthead
// relabel button


// so from here we begin to work on things. 


// first thing first, clean it



// structures


$top = array("structure","content");
$directories = array(".","about","development","lifestyle","massage","reflections","sanctuary");
$noworkdir = array("css","js","scss","vendor","img","doc");

// .skel files are framework - with placeholders for insert
// within each directory there can be two - the index, and a page
// all non-index pages in a directory have the same format


// .content files are content to go into the placeholders



$dirs["."]=new stdClass();
$dirs["about"]=new stdClass();
$dirs["development"]=new stdClass();
$dirs["lifestyle"]=new stdClass();
$dirs["massage"]=new stdClass();
$dirs["reflections"]=new stdClass();
$dirs["sanctuary"]=new stdClass();


$dirs["."]->level = 0;
$dirs["."]->pagecount = 0;
$dirs["about"]->level = 1;
$dirs["about"]->pagecount = 0;
$dirs["development"]->level = 1;
$dirs["development"]->pagecount = 0;
$dirs["lifestyle"]->level = 1;
$dirs["lifestyle"]->pagecount = 0;
$dirs["massage"]->level = 1;
$dirs["massage"]->pagecount = 0;
$dirs["reflections"]->level = 1;
$dirs["reflections"]->pagecount = 0;
$dirs["sanctuary"]->level = 1;
$dirs["sanctuary"]->pagecount = 0;


$topnav = <<<EOD

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="./about/index.html">What is Yomonsni?</a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="./massage/index.html">Massage</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./sanctuary/index.html">Massage</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./development/index.html">Development</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./lifestyle/index.html">Lifestyle</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./reflections/index.html">Reflections</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


EOD;


$subtopnav = <<<EOD

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="../about/index.html">What is Yomonsni?</a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="../massage/index.html">Massage</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../sanctuary/index.html">Sanctuary</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../development/index.html">Development</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../lifestyle/index.html">Lifestyle</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../reflections/index.html">Reflections</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


EOD;



$footer = <<<EOD

  <!-- Footer -->
  <footer class="small text-center text-white-50">
    <div class="container">
      <div class="row">
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="./terms.html">Terms and Conditions</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="./ethical-code.html">Ethical Code</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="./testimonials.html">Testimonials</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="./booking.html">Bookings and appointments</a></p>
      <p class="mb-0">For massage and development</p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="https://docs.google.com/forms/d/e/1FAIpQLSdPldPYSniomHgKaVKatOjVljJG9sM9OhZq5k0tYwusMFuUCA/viewform?usp=sf_link">General Contact</a></p>
	</div>
      </div row>
    <div class="row">
      <div class="col">
      <p class="mb-0">&nbsp</p>
      </div>
    </div>
    <div class="row">
      <div class="col">
      <p>Copyright &copy; Yomonsni 2019-2026</p>
      </div>
    </div>
    </div>
  </footer>

  <!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Plugin JavaScript -->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for this template -->
  <script src="js/grayscale.min.js"></script>

</body>

</html>



EOD;


$subfooter = <<<EOD


  <!-- Footer -->
  <footer class="small text-center text-white-50">
    <div class="container">
      <div class="row">
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="../terms.html">Terms and Conditions</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="../ethical-code.html">Ethical Code</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="../testimonials.html">Testimonials</a></p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="../booking.html">Bookings and appointments</a></p>
      <p class="mb-0">For massage and development</p>
	</div>
	<div class="col-6 col-sm">
	  <p class="mb-0"><a href="https://docs.google.com/forms/d/e/1FAIpQLSdPldPYSniomHgKaVKatOjVljJG9sM9OhZq5k0tYwusMFuUCA/viewform?usp=sf_link">General Contact</a></p>
	</div>
      </div row>
    <div class="row">
      <div class="col">
      <p class="mb-0">&nbsp</p>
      </div>
    </div>
    <div class="row">
      <div class="col">
      <p>Copyright &copy; Yomonsni 2019-2026</p>
      </div>
    </div>
    </div>
  </footer>

  <!-- Bootstrap core JavaScript -->
  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Plugin JavaScript -->
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for this template -->
  <script src="../js/grayscale.min.js"></script>

</body>

</html>



EOD;








$dirs["about"]->subnav = <<<EOD

EOD;




$dirs["massage"]->subnav = <<<EOD
  <div class="col-1 bg-faded">
	<!-- sub-nav -->
	<p class="mb-0">Massage</p>
	<p class="mb-0"><a href="./index.html">Overview</a></p>
	<p class="mb-0">&nbsp</p>
	<p class="mb-0" tw>Deeper look</p>
	<p class="mb-0"><a href="./mind.html">Mind</a></p>
	<p class="mb-0"><a href="./body.html">Body</a></p>
	<p class="mb-0"><a href="./self.html">Self</a></p>
	<p class="mb-0"></p>
	<p class="mb-0">&nbsp</p>
	<p class="mb-0" tw>Optional inclusions</p>
	<p class="mb-0"><a href="./hot-stone.html">Hot stone</a></p>
	<p class="mb-0"><a href="./cold-stone.html">Cold stone</a></p>
	<p class="mb-0"><a href="./yoni.html">Yoni</a></p>
	<p class="mb-0">&nbsp</p>
  </div subnav>
EOD;


$dirs["sanctuary"]->subnav = <<<EOD

EOD;

$dirs["development"]->subnav = <<<EOD

EOD;

$dirs["lifestyle"]->subnav = <<<EOD

EOD;

$dirs["reflections"]->subnav = <<<EOD

EOD;







//
//$filecontents = file("working/index.html");
//foreach ($filecontents as $line) {
//    echo "         $line\n";
//}




// so, want to walk the directory structure and read in skeleton pages
// there will be an index.skel and a page.skel at each actual dir level

$readprefix="fresh-content/content/";
$writeprefix="fresh-content/working/";

// clean up the destination, so nothing is left over
system ("rm -rf $writeprefix/*");

// clean up source of transient files
system ("rm -rf $readprefix/*~");
system ("rm -rf $readprefix/*/*~");

foreach ($directories as $which) {
    if ($dh=opendir("$readprefix$which")) {
        //        print "hey, opened $readprefix$which\n";
        while (($f = readdir($dh)) !== false) {
            //           print "so, working with $readprefix$which/$f\n";
            if (is_dir("$readprefix$which/$f")) {
                //                print "    skipping dir $f\n";
            } else {
                switch ($f) {
                case ".":
                case "..":
                    //                    print "   ignoring $f\n";
                    break;
                case "index.skel":
                    //                    print "   it's a skeleton index\n";
                    $dirs[$which]->indexskel = file("$readprefix$which/index.skel");
                    break;
                case "page.skel":
                    //                    print "   it's a skeleton page\n";
                    $dirs[$which]->pageskel = file("$readprefix$which/page.skel");
                    break;
                case "favicon.ico":
                case "gulpfile.js":
                    break;
                case (preg_match("/(.*\.con)^/i",$f,$m)?!$f:$f):
                    //                    print "   $f it's a content page\n";
                    $pagecount = $dirs[$which]->pagecount++;
                    $dirs[$which]->page[$pagecount] = file("$readprefix$which/$f");
                    $dirs[$which]->pagename[$pagecount] = $f;
                    break;
                default:
                    print ("oops - don't know what $f is\n");
                    break;
                }
            }
        }
    }
    //    $dirs[$which]->indexskel = file("$readprefix$which/index.skel");
    //    $dirs[$which]->pageskel = file("$readprefix$which/page.skel");
    
}


// so, in theory I now have all the directories and contents loaded up here... 
// so now I want to begin to write to the destination. keep in mind that the subnav
// hardcoded above, so don't need to work that out. 

foreach ($directories as $which) {
    print "$which\n";
    $level = $dirs[$which]->level;
    print "  level $level\n";
    system ("mkdir -p $writeprefix$which",$ret);
    if ($dirs[$which]->indexskel) {
        print "  has an index\n";
    }
    if ($dirs[$which]->pageskel) {
        print "  has a pagrs skel\n";
    }
    $pages = $dirs[$which]->pagecount;
    print "  has $pages pages\n";
    // read through all the content files, pulling the right bits into temp variables
    // and then shove out into a html as a site
    for ($i=0; $i<$pages; $i++) {
        $thispage = new StdClass();
        $thispage->bgimage = "";
        $thispage->title = "";
        $thispage->subhead = "";
        $thispage->subdeah = "";
        $thispage->stuffing = "";

        print "    checking page $i -- ";
        $fn = $dirs[$which]->pagename[$i];
        if (preg_match("|(.*?).con|",$fn,$matches)) {
            $f=$matches[1];
        }
        print "processing $fn --   $f\n";
        foreach ($dirs[$which]->page[$i] as $line) {
            //            print "--- reading - $line\n";

            switch ($line) {
            case ( preg_match("|TITLE=(.*)$|",$line,$matches)?$line:!$line):
                $m = $matches[1];
                print "      title: $m\n";
                $thispage->title = $m;
                break;
            case ( preg_match("|BGIMAGE=(.*)$|",$line,$matches)?$line:!$line):
                $m = $matches[1];
                print "      bgimage: $m\n";
                $thispage->bgimage = $m;
                break;
            case ( preg_match("|SUBHEAD=(.*)$|",$line,$matches)?$line:!$line):
                $m = $matches[1];
                print "      subhead: $m\n";
                $thispage->subhead = $m;
                break;
            case ( preg_match("|SUBDEAH=(.*)$|",$line,$matches)?$line:!$line):
                $m = $matches[1];
                print "      subdeah: $m\n";
                $thispage->subdeah = $m;
                break;
            default:
                $thispage->stuffing .= $line;
                break;
            }





                
        }
        // now, read the skel page that's right, and start writing into the html
        $out = fopen("$writeprefix$which/$f.html","w");
        switch ($f) {
        case "index":
            print "      handling an index.skel\n";
            $outsource = $dirs[$which]->indexskel;
            break;
        default:
            print "      handling a page.skel\n";
            $outsource = $dirs[$which]->pageskel;
            break;
        }
        foreach ($outsource as $line) {
            $err = 0;
            $lim = -1;
            if ( preg_match("|TITLE|",$line,$matches)) {
                $t = $thispage->title;
                $foo = preg_replace("|(TITLE)|",$t,$line,$lim,$err);
                $line = $foo;
            }
            
            if ( preg_match("|BGIMAGE|",$line,$matches)) {
                $t = $thispage->bgimage;
                $foo = preg_replace("|(BGIMAGE)|",$t,$line,$lim,$err);
                $line = $foo;
            }
            if ( preg_match("|SUBHEAD|",$line,$matches)) {
                $t = $thispage->subhead;
                $foo = preg_replace("|(SUBHEAD)|",$t,$line,$lim,$err);
                $line = $foo;
            }
            if ( preg_match("|SUBDEAH|",$line,$matches)) {
                $t = $thispage->subdeah;
                $foo = preg_replace("|(SUBDEAH)|",$t,$line,$lim,$err);
                $line = $foo;
            }
            if ( preg_match("|STUFFING|",$line,$matches)) {
                $t = $thispage->stuffing;
                $foo = preg_replace("|(STUFFING)|",$t,$line,$lim,$err);
                $line = $foo; 
            }
            if ( preg_match("|FOOTER|",$line,$matches)) {
                if ($level) {
                    $t = $subfooter;
                } else {
                    $t = $footer;
                }
                $foo = preg_replace("|(FOOTER)|",$t,$line,$lim,$err);
                $line = $foo; 
            }
            fwrite ($out,$line);
        }
            
    }
    
}


foreach ($noworkdir as $which) {
    //    system ("rsync -av --delete $readprefix$which $writeprefix",$ret);

}




// to build push ready copy lots of things
foreach ($noworkdir as $which) {
        print "rsync -av --delete $readprefix$which $writeprefix\n";
        system ("rsync -a --delete $readprefix$which $writeprefix",$ret);

}

system ("rsync -a $writeprefix \/tmp\/foo",$ret);







// push steps
// aws s3 rm s3://yomonsni.com/about --recursive
// aws s3 sync . s3://yomonsni.com --acl public-read

// aws s3 sync fresh-content/working/ s3://yomonsni.com --acl public-read



?>