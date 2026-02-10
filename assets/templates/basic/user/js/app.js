'use strict';

// menu options custom affix
var fixed_top = $(".header");
$(window).on("scroll", function(){
    if( $(window).scrollTop() > 50){  
        fixed_top.addClass("animated fadeInDown menu-fixed");
    }
    else{
        fixed_top.removeClass("animated fadeInDown menu-fixed");
    }
});

// mobile menu js
$(".navbar-collapse>ul>li>a, .navbar-collapse ul.sub-menu>li>a").on("click", function() {
  const element = $(this).parent("li");
  if (element.hasClass("open")) {
    element.removeClass("open");
    element.find("li").removeClass("open");
  }
  else {
    element.addClass("open");
    element.siblings("li").removeClass("open");
    element.siblings("li").find("li").removeClass("open");
  }
});

// PAGE TRANSITION

// wow js init
new WOW().init();

// lightcase plugin init
$('a[data-rel^=lightcase]').lightcase();

// main wrapper calculator
var bodySelector = document.querySelector('body');
var header = document.querySelector('.header');
var footer = document.querySelector('.footer-section');
(function(){
  if(bodySelector.contains(header) && bodySelector.contains(footer)){
    var headerHeight = document.querySelector('.header').clientHeight;
    var footerHeight = document.querySelector('.footer-section').clientHeight;

    // if header isn't fixed to top
    var totalHeight = parseInt( headerHeight, 10 ) + parseInt( footerHeight, 10 ) + 'px'; 
    
    // if header is fixed to top
    // var totalHeight = parseInt( footerHeight, 10 ) + 'px'; 
    console.log(totalHeight);
    var minHeight = '100vh';
    document.querySelector('.main-wrapper').style.minHeight = `calc(${minHeight} - ${totalHeight})`;
  }
})();

// Animate the scroll to top
$(".scroll-top").on("click", function(event) {
	event.preventDefault();
	$("html, body").animate({scrollTop: 0}, 300);
});

$(function () {
  $('[data-toggle="tooltip"]').tooltip({
    boundary: 'window'
  })
});

$("[data-paroller-factor]").paroller();

// custom input animation js
$('.custom--form-group .form--control').on('input', function(){
  let passfield = $(this).val();
  if (passfield.length < 1){
      $(this).removeClass('hascontent');
  }else{
      $(this).addClass('hascontent');
  }
});

/* ==============================
					slider area
================================= */

// brand-slider
$('.brand-slider').slick({
  // autoplay: true,
  autoplaySpeed: 2000,
  dots: false,
  infinite: true,
  speed: 300,
  slidesToShow: 5,
  arrows: false,
  slidesToScroll: 1,
  responsive: [
    {
      breakpoint: 992,
      settings: {
        slidesToShow: 1,
      }
    }
  ]
});

// payment-method-slider
$('.testimonial-slider').slick({
  // autoplay: true,
  autoplaySpeed: 2000,
  dots: false,
  infinite: true,
  speed: 300,
  slidesToShow: 3,
  arrows: true,
  nextArrow: '<div class="next"><i class="las la-long-arrow-alt-right"></i></div>',
    prevArrow: '<div class="prev"><i class="las la-long-arrow-alt-left"></i></div>',
  slidesToScroll: 1,
  responsive: [
    {
      breakpoint: 992,
      settings: {
        slidesToShow: 1,
      }
    }
  ]
});

function defaultBehavior(){
  var inputElements = $('[type=text],[type=password],select,textarea');
  $.each(inputElements, function (index, element) {
      element = $(element);
      element.closest('.form-group').find('label').attr('for',element.attr('name'));
      element.attr('id',element.attr('name'))
  });

  $.each($('input:not([type=checkbox]):not([type=hidden]), select, textarea'), function (i, element) {

      if (element.hasAttribute('required')) {
          $(element).closest('.form-group').find('label').addClass('required');
      }

  });
}

defaultBehavior();

Array.from(document.querySelectorAll('table')).forEach(table => {
  let heading = table.querySelectorAll('thead tr th');
  Array.from(table.querySelectorAll('tbody tr')).forEach((row) => {
      Array.from(row.querySelectorAll('td')).forEach((colum, i) => {
        if (colum.hasAttribute('colspan') && i == 0) {
          return false;
        }
        colum.setAttribute('data-label', heading[i].innerText)
      });
  });
});