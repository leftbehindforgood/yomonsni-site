<?php
// Page shell: doctype/head/body wrapper shared by every page shape.
// Expects: $prefix, $title, $bgimage, $nav_html, $content_html, $footer_html.
$bg = isset($bgimage) && $bgimage !== '' ? $bgimage : 'default-bg.jpg';
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?php echo htmlspecialchars($title); ?></title>

  <!-- Bootstrap core CSS -->
  <link href="<?php echo asset_url($prefix, 'vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">

  <!-- Custom fonts for this template -->
  <link href="<?php echo asset_url($prefix, 'vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Varela+Round" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template -->
  <link href="<?php echo asset_url($prefix, 'css/myfunk.css'); ?>" rel="stylesheet">

</head>

<body style="background-image: linear-gradient(to bottom, rgba(22, 22, 22, 0.3) 0%, rgba(22, 22, 22, 0.7) 75%, #161616 100%), url('<?php echo asset_url($prefix, "img/$bg"); ?>');">

<?php echo $nav_html; ?>

<?php echo $content_html; ?>

<?php echo $footer_html; ?>

</body>
</html>
