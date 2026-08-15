<?php


// just pushes yomonsni site

echo "howdy, pushing yomonsni\n";

// push steps
// aws s3 rm s3://yomonsni.com/about --recursive
// aws s3 sync . s3://yomonsni.com --acl public-read

system ("aws s3 sync fresh-content/working/ s3://yomonsni.com --acl public-read",$ret);



?>