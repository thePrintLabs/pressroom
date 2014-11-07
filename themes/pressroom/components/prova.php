<?php
/*
* theme:        Pressroom
* rule:         post
* name:         aaa
* description:  default layout
*/

// temporary fix to enable wp hook & filters inside PR-themes
require_once('inc/function.php');
require('components/coverimage.php');

$stylesheet = 'assets/css/styles.css';

?>
<!DOCTYPE html>
    <?php
        require('partials/head.php');
    ?>
    <body class="<?php echo $post->post_name; ?>">
    <?php
        require('partials/content.php');
    ?>
    <script type="text/javascript" src="assets/js/scripts.min.js"></script>
    </body>
</html>
