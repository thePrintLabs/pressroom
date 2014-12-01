<?php

/*
*     
*     theme:        Starterr
*     rule:         post
*     name:         basic-article
*     description:  default layout
*     
*/

require('components/coverimage.php');

$stylesheet = 'assets/css/styles.a38a4712.css';

?>
<!DOCTYPE html>
	<?php
		require('partials/head.php');
	?>
	<body class="<?php echo $post->post_name; ?>">
	<?php
		require('partials/content.php');
	?>
	<script type="text/javascript" src="assets/js/scripts.min.665cf2e5.js"></script>
	</body>
</html>
