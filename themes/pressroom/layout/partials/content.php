<div class="container"> 
	<!-- <div class="wrapper"> -->
		<article id="skrollr-body">
			<?php if($image): ?>
			<header class="cover">
				<div class="cover__image" style="background-image: url('<?php echo $image[0]; ?>');">
					<div class="cover__overlay"></div>
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