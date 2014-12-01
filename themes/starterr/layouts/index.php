<?php

/*
*
*     theme:        Starterr
*     rule:         cover
*     name:         cover
*     description:  Edition Cover layout file
*
*/

$posts = pr_get_edition_posts( $edition, true );
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/main.css">

    </head>
    <body style="background:transparent">
        <h1>Hello Pressroom</h1>
        <?php
            if ( $posts->have_posts() ) {
                while ( $posts->have_posts() ) {
                    $posts->the_post();
                    the_title();
                    the_content();
                }
            }
        ?>
    </body>
</html>
