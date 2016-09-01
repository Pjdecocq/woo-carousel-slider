jQuery(document).ready(function($){
	$(wc_slider.selector).slick({
		fade: (wc_slider.fade?true:false),
		dots: (wc_slider.dots?true:false),
		autoplay: (wc_slider.autoplay?true:false),
		autoplaySpeed: wc_slider.autoplaySpeed,
		slidesToShow: wc_slider.slidesToShow,
		slidesToScroll: wc_slider.slidesToScroll
	});
});