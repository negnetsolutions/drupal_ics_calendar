jQuery(document).ready(function(){

  //add a print link
  if(window.print && jQuery(".event_calendar-nav-wrapper").length) {
    jQuery(".event_calendar").append("<p class='printer_icon'><a  href='javascript:window.print();'><span>&nbsp;</span>Print Calendar</a></p>");
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
    if( (offset_top + dd_height) > window_height ) {
      offset_top = window_height - (offset_top + dd_height) - dd_height;
      jQuery('dd',this).css('top', offset_top);
    }
  });


});

