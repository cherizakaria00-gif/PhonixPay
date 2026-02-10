(function ($) {
  "use strict";

  // ============== Header Hide Click On Body Js Start ========
  $('.header-button').on('click', function() {
    $('.body-overlay').toggleClass('show')
  }); 
  $('.body-overlay').on('click', function() {
    $('.header-button').trigger('click')
    $(this).removeClass('show');
  }); 
  // =============== Header Hide Click On Body Js End =========
  // ==========================================
  //      Start Document Ready function
  // ==========================================
  $(document).ready(function () {
    
    // ================== Password Show Hide Js Start ==========
    $(".toggle-password").on('click', function () {
      $(this).toggleClass("fa-eye");
      var input = $($(this).attr("id"));
      if (input.attr("type") == "password") {
        input.attr("type", "text");
      } else {
        input.attr("type", "password");
      }
    });
  // =============== Password Show Hide Js End =================
   
  // ========================== Header Hide Scroll Bar Js Start =====================
  $('.navbar-toggler.header-button').on('click', function() {
    $('body').toggleClass('scroll-hidden-sm')
  }); 
  $('.body-overlay').on('click', function() {
    $('body').removeClass('scroll-hidden-sm')
  }); 
  // ========================== Header Hide Scroll Bar Js End =====================

  });
  // ==========================================
  //      End Document Ready function
  // ==========================================

  // ========================= Preloader Js Start =====================
    $(window).on("load", function(){
      $('.preloader').fadeOut(); 
    })
    // ========================= Preloader Js End=====================

    // ========================= Header Sticky Js Start ==============
    $(window).on('scroll', function() {
      if ($(window).scrollTop() >= 300) {
        $('.header').addClass('fixed-header');
      }
      else {
          $('.header').removeClass('fixed-header');
      }
    }); 
    // ========================= Header Sticky Js End===================
    
    //============================ Scroll To Top Icon Js Start =========
    var btn = $('.scroll-top');

    $(window).scroll(function() {
      if ($(window).scrollTop() > 300) {
        btn.addClass('show');
      } else {
        btn.removeClass('show');
      }
    });

    btn.on('click', function(e) {
      e.preventDefault();
      $('html, body').animate({scrollTop:0}, '300');
    });
//========================= Scroll To Top Icon Js End ======================

$('.custom--dropdown > .custom--dropdown__selected').on('click', function() {
  $(this).parent().toggleClass('open');
});

$('.custom--dropdown > .dropdown-list > .dropdown-list__item').on('click', function() {
  $('.custom--dropdown > .dropdown-list > .dropdown-list__item').removeClass('selected');
  $(this).addClass('selected').parent().parent().removeClass('open').children('.custom--dropdown__selected').html($(this).html());             
});

$(document).on('keyup', function(evt) {
  if ((evt.keyCode || evt.which) === 27) {
      $('.custom--dropdown').removeClass('open');
  }
});

$(document).on('click', function(evt) {
  if ($(evt.target).closest(".custom--dropdown > .custom--dropdown__selected").length === 0) {
      $('.custom--dropdown').removeClass('open');
  }
});   

function defaultBehavior(){
  var inputElements = $('[type=text],select,textarea');
  $.each(inputElements, function (index, element) {
      element = $(element);
      element.closest('.form-group').find('label').attr('for',element.attr('name'));
      element.attr('id',element.attr('name'))
  });

  $.each($('input, select, textarea'), function (i, element) {
      var elementType = $(element);
      if(elementType.attr('type') != 'checkbox'){
          if (element.hasAttribute('required')) {
              $(element).closest('.form-group').find('label').addClass('required');
          }
      }
  });
}

defaultBehavior();

Array.from(document.querySelectorAll('table')).forEach(table => {
  let heading = table.querySelectorAll('thead tr th');
  Array.from(table.querySelectorAll('tbody tr')).forEach((row) => {
      Array.from(row.querySelectorAll('td')).forEach((colum, i) => {
          colum.setAttribute('data-label', heading[i].innerText)
      });
  });
});

let disableSubmission = false;
$('.disableSubmission').on('submit',function(e){
    if (disableSubmission) {
    e.preventDefault()
    }else{
    disableSubmission = true;
    }
});

})(jQuery);
