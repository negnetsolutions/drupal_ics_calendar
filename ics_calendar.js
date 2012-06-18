jQuery(document).ready(function(){

  //add a print link
  if(window.print && jQuery(".event_calendar-nav-wrapper").length) {
    jQuery('.printer_icon').css('display','block');
  }

  jQuery('.event_calendar dl').hover(function(event){
    var window_width = jQuery(document).width();
    var window_height = jQuery(window).height();
    var dd_width = jQuery('dd', this).width();
    var dd_height = jQuery('dd', this).height();
    var offset_top = jQuery('dd',this).offset().top - jQuery(window).scrollTop();
    var offset_left = jQuery('dd',this).offset().left;
    var dd_x = offset_left;

    //check width
    if( (dd_x + dd_width) > window_width ) {
      offset_left = window_width - (dd_x + dd_width) - dd_width;
      jQuery('dd',this).css('left', offset_left);
    }
    //check height
    if( (offset_top + dd_height + 10) > window_height ) {
      offset_top = window_height - (offset_top + dd_height) - dd_height + dd_height - 10;
      jQuery('dd',this).css('top', offset_top);
    }
  });


});

