<main class="home">
	<header>
		<h1><?php __($title); ?></h1>
	</header>
	<div class="main-body home">
		<?php

		if (empty($node_schemas)) {

		?>
		<p><?php __(_m('home_empty')); ?></p>
		<?php

		} else {

		?>
		<p class="intro"><?php __(_m('home', $total_nodes)); ?></p>
		<nav class="collection">
			<ul>
			<?php

			foreach ($node_schemas as $node_schema) {

			?>
				<li class="object"><a href="<?php __(_e($node_schema->getCollectionPath())); ?>"><?php __(_m('node_schema_name_total', $node_schema->getDisplayName(), $node_totals[$node_schema->getName()])); ?></a></li>
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
