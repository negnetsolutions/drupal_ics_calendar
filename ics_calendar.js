jQuery(document).ready(function(){

  //add a print link
  if(window.print && jQuery(".event_calendar-nav-wrapper").length) {
    jQuery('.printer_icon').css('display','block');
    jQuery('.printer_icon a').click(function(){
      window.print();
      return false;
    });
  }

  jQuery('.event_calendar dl').hover(function(event){
    var window_width = jQuery(window).width();

    // position the dd
    var dd_x = jQuery(this).offset().left;
    var dd_y = jQuery(this).offset().top + jQuery(this).height();
    jQuery('dd', this).fadeIn();
    jQuery('dd',this).offset({ top: dd_y, left: dd_x });

    var dd_width = jQuery('dd', this).width();

    //check width
    if( (dd_x + dd_width) > window_width ) {
      var offset_left = window_width - dd_width - 10;
      jQuery('dd', this).offset({ left: offset_left });
    }
  }, function(event){
    jQuery('dd', this).css('display','none');
  });


});

