var TwitterSettings = {
	TextLength: 140,
	BitlyUrlLength: 20,
	SeperatorLength: 1,
};

jQuery(document).ready(function() {
	jQuery('#wasp-twitter-text').keyup(function(e) {
		var text   = jQuery(this).val(),
			length = TwitterSettings.TextLength -
					 TwitterSettings.BitlyUrlLength - 
					 TwitterSettings.SeperatorLength,
		    left   = 0;
		
		left = length - text.length;
		
		jQuery('#wasp-twitter-characters-left').html(left);
	}).keyup();
});