<style>
#scrape-products .mw-ui-progress { height: auto; }
#scrape-products .mw-ui-progress-percent { font-size: 1rem; }
</style>

<div class="mw-module-admin-wrap" id="scrape-products">

	<module type="admin/modules/info" />

	<div class="mw-ui-row">
		<div class="mw-ui-col" style="padding-right: 1em;">
			<label>
				Source:
				<div class="mw-dropdown mw-dropdown-default" id="import-source">
					<span class="mw-dropdown-value mw-ui-btn mw-ui-btn-info mw-dropdown-val">Choose</span>
					<div style="display: none;" class="mw-dropdown-content">
					<ul>
						<?php foreach(mw('import_content')->getSources() as $s => $source): ?>
						<li value="<?php echo $s; ?>">
							<?php echo strtoupper($source['name']); ?>
						</li>
						<?php endforeach; ?>
					</ul>
					</div>
				</div>
			</label>
			<br>
			<br>
			<div class="mw-ui-box mw-ui-box-content on-selected">
				<span id="product-count">?</span>
				imported products
			</div>
		</div>
		<div class="mw-ui-col mw-ui-box mw-ui-box-content on-selected">
			<div id="container-resume">
				<button class="mw-ui-btn" id="btn-halt">
					<span class="mw-icon-close"></span>
					<?php _e('Stop Import'); ?>
				</button>
				<button class="mw-ui-btn" id="btn-resume">
					<span class="mw-icon-play"></span>
					<?php _e('Resume Import'); ?>
				</button>
			</div>
			<button class="mw-ui-btn mw-ui-btn-notification" id="btn-import">
				<span class="mw-icon-refresh"></span>
				<?php _e('Import'); ?>
			</button>

			<h3 id="eta"></h3>

			<div class="mw-ui-progress" id="progress">
				<div class="mw-ui-progress-bar"></div>
				<span class="mw-ui-progress-percent"></span>
			</div>

			<table width="100%">
			</table>
		</div>
	</div>
</div>
