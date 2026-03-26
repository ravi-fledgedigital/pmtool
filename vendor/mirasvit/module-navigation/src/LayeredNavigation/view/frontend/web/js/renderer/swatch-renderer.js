define([
	'jquery',
	'Magento_Swatches/js/swatch-renderer'
], function ($) {
	'use strict';

	return function(config, element) {
	    $(element).find('[data-option-type="1"], [data-option-type="2"], [data-option-type="0"], [data-option-type="3"]')
	        .SwatchRendererTooltip();
		}
	}
);
