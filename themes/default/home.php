<main class="home">
	<header>
		<h1><?php __($title); ?></h1>
	</header>
	<div class="body">
		<?php

		if (empty($node_schemas)) {

		?>
		<p><?php __(_m('home_empty')); ?></p>
		<?php

		} else {

		?>
		<p class="intro"><?php __(_m('home')); ?></p>
		<nav class="collection">
			<ul>
			<?php

			foreach ($node_schemas as $node_schema) {

			?>
				<li><a href="/<?php __($node_schema->getSlug()); ?>/"><?php __($node_schema->getName()); ?></a></li>
			<?php

			}

			?>
			</ul>
		</nav>
		<?php

		}

		?>
	</div>
</main>
