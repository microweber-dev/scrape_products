<style>
#scrape-products ul:not(.depth-1) {
	margin: 0 1em;
}
#scrape-products ul {
	list-style: none;
}
#category-selector {
	border: 1px solid #eee;
	padding: .5em;
	margin: .5em 0;
}
#category-selector a {
	transition: background-color .3s;
	padding: .2em;
	display: block;
}
#category-selector a.active {
	background-color: #333;
	color: #fff;
	font-weight: bold;
}
.source-name {
	font-weight: bold;
}
.source-cat {
}
.source-uri {
	font-size: .8em;
	color: #666;
	margin-bottom: .5em;
}
#sources-list .mw-ui-box {
	margin-bottom: .5em;
}
.product-item {
	transition: background-color .3s;
	cursor: default;
}
.product-item:hover {
	background-color: #ccc;
}
.product-item .product-tip {
	display: none;
}
.products-box > .mw-ui-box-header {
	cursor: pointer;
}
.products-box > .mw-accordion-content {
	display: none;
}
.refresh-source {
	float: right;
}
#sources-filter input {
	position: relative;
	top: 2px;
}
#sources-filter .sources-filter {
	padding-right: 2em;
}
#modal-domselector {
	position: fixed;
	width: 100%;
	left: 0;
	height: 100%;
	top: 0;
	padding-left: 10%;
	background: rgba(0,0,0,.9);
	display: none;
}
#modal-domselector > .header {
	color: #fff;
	padding: 0 1em;
	margin-bottom: 2em;
	text-transform: uppercase;
}
#modal-domselector > .header > * {
	font-weight: bold;
}
#modal-domselector > .header > .mw-ui-btn {
	float: right;
	margin-left: .5em;
}
#modal-domselector iframe {
	width: 98%;
	height: 84%;
	border: none;
}
</style>

<div class="mw-module-admin-wrap" id="scrape-products">

	<module type="admin/modules/info" />

	<h3><?php _e('Product Sources'); ?> (<?php echo ScrapeProductSource::count() ?>):</h3>

	<div class="mw-ui-field-holder">
		<div id="sources-filter">
			<input class="mw-ui-field mw-ui-field-medium" type="text" value="<?php echo scrape_products_sources_filter(); ?>" placeholder="<?php _e('Search'); ?>...">
			<div class="mw-ui-btn-nav">
				<span class="mw-ui-btn mw-ui-btn-medium sources-filter">
					<span class="mw-icon-magnify"></span>
					<?php _e('Filter'); ?>
				</span>
				<span class="mw-ui-btn mw-ui-btn-medium sources-clear">
					<span class="mw-icon-close"></span>
					<?php _e('Clear'); ?>
				</span>
			</div>
		</div>
		<?php echo ScrapeProduct::count() ?> <?php _e('products found'); ?>
	</div>

	<table width="100%">
		<tr>
			<td width="45%" valign="top" id="sources-list">
				<?php foreach(scrape_products_sources_get() as $source): ?>
				<?php $productCount = $source->products()->count(); ?>
				<div class="mw-ui-box mw-ui-box-content">
					<a href="javascript:void(0);" data-source="<?php echo $source->id; ?>" class="remove-source pull-right">
						<span class="mw-icon-close"></span>
					</a>

					<div class="source-name"><?php echo $source->name; ?></div>

					<div class="source-cat">
						<span class="mw-icon-category"></span>
						<?php
							$category = get_category_by_id($source->category_id);
							echo $category['title'];
						?>
					</div>

					<div class="source-uri"><?php echo $source->uri; ?></div>

					<div class="mw-ui-box products-box" id="products-<?php echo $source->id; ?>">
						<div class="mw-ui-box-header">
							<a href="javascript:void(0);" class="mw-ui-btn mw-ui-btn-small refresh-source" data-source="<?php echo $source->id; ?>">
							  <span class="mw-icon mw-icon-reload"></span>
							  Refresh
							</a>
							<span class="mw-icon-app-browsers-outline"></span>
							<span><?php echo $productCount ?> <?php _e('products found'); ?></span>
						</div>
						<?php if($productCount): ?>
						<div class="mw-ui-box-content mw-accordion-content">
							<?php foreach($source->products as $product): ?>
							<div class="product-item tip" data-tip="#tip-product-<?php echo $product->id; ?>" data-tipposition="top-left">
								<a href="<?php echo $product->uri; ?>" target="_blank" title="View original product page">
									<span class="mw-icon-forward"></span>
								</a>
								<span class="product-name">
									<?php echo $product->name; ?>
								</span>
								<div class="product-tip" id="tip-product-<?php echo $product->id; ?>">
									<img src="<?php echo $product->image; ?>">
									<p>Updated <?php echo $product->updated_at->diffForHumans(); ?></p>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>

				<?php $pagesCount = scrape_products_sources_pages_count(); ?>
				<?php if($pagesCount > 1): ?>
				<hr>
				<div class="mw-paging">
					<?php for($p=1; $p<=$pagesCount; $p++): ?>
	                <a href="javascript: void(0);" <?php if($p == scrape_products_sources_page()) echo 'class="active"'; ?> data-sources-page="<?php echo $p; ?>">
	                	<?php echo $p; ?>
	                </a>
					<?php endfor; ?>
				</div>
				<?php endif; ?>
			</td>
			<td width="10%">&nbsp;</td>
			<td width="45%" valign="top">
				<div class="mw-ui-box">
					<div class="mw-ui-box-header">
						<span class="mw-icon-plus"></span>
						<span><?php _e('Add new product source'); ?></span>
					</div>
					<div class="mw-ui-box-content">
              			<div class="mw-ui-field-holder">
							<label>
								<?php _e('URL of product listing (category)'); ?>:
								<input type="text" id="new-uri" class="mw-ui-field mw-ui-filed-big mw_option_field w100">
							</label>
						</div>

              			<div class="mw-ui-field-holder">
							<label>
								<?php _e('Display name'); ?>:
								<input type="text" id="new-name" class="mw-ui-field mw-ui-filed-big mw_option_field w100">
							</label>
						</div>

              			<div class="mw-ui-field-holder">
							<label><?php _e('Map to website category'); ?>:</label>
						</div>

              			<div class="mw-ui-field-holder">
						  	<label>
								New category:
								<input type="text" id="new-category-name" class="mw-ui-field mw-ui-filed-big mw_option_field w100">
							</label>
						</div>
              			<div class="mw-ui-field-holder">
						  	<label>Or select from existing:</label>
							<div id="category-selector">
								<?php category_tree(array('link' => '<a href="javascript:void(0);" id="category-{id}">{title}</a>')); ?>
							</div>
						</div>

              			<div class="mw-ui-field-holder" style="padding-top: 1em;">
							<a href="javascript:void(0);" class="mw-ui-btn" id="add-source">
							  <span class="mw-icon mw-icon-plus"></span>
							  <?php _e('Add Product Source'); ?>
							</a>
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>

</div>

<div id="modal-domselector">
	<div class="header">
		<button class="ok mw-ui-btn loaded">
			<span class="mw-icon mw-icon-check"></span>
			Done
		</button>
		<button class="cancel mw-ui-btn">
			Cancel
		</button>
		<h3 class="loading">
			<span class="mw-icon mw-icon-load-a"></span>
			Please wait...
		</h3>
		<h3 class="loaded">Select element:</h3>
	</div>
	<iframe src="http://google.com/" class="loaded"></iframe>
</div>