<?php
/*
* theme: Pressroom
* rule: post
* name: fullwidth
*/
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title><?php the_title(); ?></title>
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <link rel="stylesheet" href="assets/css/styles.css">
    </head>
    <body>
        <div class="container">
            <div class="wrapper">
                <article>
                    <h1>
                        <?php the_title(); ?>
                    </h1>
                    <div class="main">
                        <?php the_content(); ?>
                    </div>
                </article>
                <!-- <a href="<?=home_url($path = 'example')?>">post me</a> -->
            </div>
        </div>
    </body>
</html>