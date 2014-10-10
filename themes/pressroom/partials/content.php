<?php

// Content

?>
<div class="container"> 
	<!-- <div class="wrapper"> -->
		<article id="skrollr-body">
			<?php if($image): ?>
			<header class="cover">
				<div class="cover__image" style="background-image: url('<?php echo $image[0]; ?>');" 
				data-0p="transform: translate3d(0px, 0px, 0px);transform: scale3d(1, 1, 1);" 
				data-60p="transform: translate3d(0px, -600px, 0px);transform: scale3d(1.2, 1.2, 1.2);"
				>
					<div class="cover__overlay <?php echo $coverClass; ?>"></div>
					<div class="cover__wrapper">
			<?php else: ?>
				<header >
			<?php endif; ?>
					<h1 class="<?php echo $titleClass; ?>">
					<?php the_title(); ?>
					</h1>
					<div class="entry-meta <?php echo $metaClass; ?>">
						<p>
						<?php the_author(); ?>
						</p>
					</div>
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