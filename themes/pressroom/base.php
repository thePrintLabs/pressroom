<?php

/*
* theme: Pressroom
* rule: post
* name: base
*/

// temporary fix to enable wp hook & filters inside PR-themes
require_once('inc/function.php');
require('components/coverimage.php');

?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title><?php the_title(); ?></title>
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <link rel="stylesheet" href="assets/css/styles.175ff238.css">
    </head>
    <body class="<?php echo $post->post_name; ?>">
    <?php require('partials/content.php'); ?>    
    <script type="text/javascript" src="assets/js/scripts.min.856a8dac.js"></script>
    </body>
</html>