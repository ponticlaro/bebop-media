<div bebop-media-manage-attachment="{{ data.id }}">
	<h3>Ponticlaro Media</h3>

	<# if (data.po_bebop_media) { #>

		<ul class="bebop-media-manage-attachment--sizes">
			<# _.each(data.po_bebop_media.sizes, function(size_data) { #>
				<li>

					<#

					var id = data.id;

					_.each(size_data, function(value, key) {
						this[key] = value;
					}, this);

					#>

					<?php include __DIR__ .'/item.html'; ?>

				</li>
			<# }); #>
		</ul>

	<# } else { #>

		<?php include __DIR__ .'/no-data.html'; ?>

	<# } #>
</div>
