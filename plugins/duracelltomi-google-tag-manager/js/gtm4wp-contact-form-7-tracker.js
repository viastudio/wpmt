jQuery( function() {
	jQuery( ".wpcf7" )
		.on( 'mailsent.wpcf7', function(){
			window[ gtm4wp_datalayer_name ].push({
				'event': 'gtm4wp.contactForm7Submitted'
			});
		});
});
