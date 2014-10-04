<?php
/*
* theme:        Pressroom
* rule:         post
* name:         fullwidth
* description:  default layout
*/

// temporary fix to enable wp hook & filters inside PR-themes
require_once('inc/function.php');

$image_id =  get_post_thumbnail_id();
if($image_id):
    $attached_image = wp_get_attachment_metadata($image_id);
    $image = wp_get_attachment_image_src($image_id, 'full');
endif;

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
    <body class="<?php echo $post->post_name; ?>">
        <div class="container"> 
            <!-- <div class="wrapper"> -->
                <article>
                    <?php if($image): ?>
                    <header class="cover">
                        <div class="cover__image" style="background-image: url('<?php echo $image[0]; ?>');">
                            <div class="overlay check"></div>
                            <div class="cover__wrapper">
                    <?php else: ?>
                        <header class="wrapper">
                    <?php endif; ?>
                            <h1 class="cover__title check">
                            <?php the_title(); ?>
                            </h1>
                    <?php if($image): ?>
                            </div>
                        </div>
                    </header>
                    <?php else: ?>
                        </header>
                    <?php endif; ?>
                    <div class="main">
                        <?php the_content(); ?>
                    </div>
                </article>
            <!-- </div> -->
        </div>
    <script type="text/javascript" src="assets/js/scripts.min.js"></script>
    </body>
</html>