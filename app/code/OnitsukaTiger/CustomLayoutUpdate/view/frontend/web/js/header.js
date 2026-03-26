$('.header_menu_btn').on("click", function () {
    $('.header').toggleClass('no-scroll');
    $('body').toggleClass('no-scroll');
    $('.main-menu').toggleClass('is_open');
});


$('.menu-parent').click(function(e){
    $(this).parent().toggleClass('childOpen');
});