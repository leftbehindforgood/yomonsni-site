<?php
// Page shell: doctype/head/body wrapper shared by every page shape.
// Expects: $prefix, $title, $bgimage, $nav_html, $content_html, $footer_html.
// $bgimage is optional — when it's empty, no inline style is emitted at
// all, and content/css/myfunk.css's own plain `body { background-color:
// #161616; }` applies. There is deliberately no made-up fallback filename
// here: an earlier version of this file fell back to a "default-bg.jpg"
// that was never actually created, silently 404ing on every page that
// didn't set an explicit bgimage (every page shape except the hub and
// domain overviews). Don't reintroduce that.
//
// No darkening/gradient/tint is layered over the photo here (there used
// to be one, a `linear-gradient(...), url(...)` stack painted on top of
// it for text-legibility) — standing project rule: a background image
// renders exactly as its file, full transparency, no code-applied effect
// over it. If a given photo doesn't contrast enough with the page's text,
// that gets fixed by editing the image file itself (crop/grade/darken it
// in an actual image editor before it goes in content/img/), never by
// covering it up in CSS/HTML. Don't reintroduce a gradient/tint here.
$body_style = '';
if (isset($bgimage) && $bgimage !== '') {
    $url = asset_url($prefix, "img/$bgimage");
    $body_style = " style=\"background-image: url('$url');\"";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?php echo htmlspecialchars($title); ?></title>

  <link href="<?php echo asset_url($prefix, 'vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url($prefix, 'vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Varela+Round" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
  <link href="<?php echo asset_url($prefix, 'css/myfunk.css'); ?>" rel="stylesheet">

</head>

<body<?php echo $body_style; ?>>

<?php echo $nav_html; ?>

<?php echo $content_html; ?>

<?php echo $footer_html; ?>

<script src="<?php echo asset_url($prefix, 'js/toggle-expand.js'); ?>"></script>

</body>
</html>
