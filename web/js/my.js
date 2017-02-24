
function hDate(dt) {
   return dt.getUTCFullYear() + "-" + (dt.getUTCMonth() + 1) + "-" + (dt.getUTCDate());
}

function ajaxTypeahead(ev) {
   ev.stopPropagation();
   ev.preventDefault();
   //filter out up/down, tab, enter, and escape keys
   if ($.inArray(ev.keyCode, [40, 38, 9, 13, 27]) === -1) {

      var self = $(this);
      // We get the URL from input
      var urljson = self.attr("data-link");
      //set typeahead source to empty
      self.data('typeahead').source = [];
      //active used so we aren't triggering duplicate keyup events
      if (!self.data('active') && self.val().length > 0) {

         self.data('active', true);
         //Do data request.
         $.ajax({
            url: urljson,
            type: 'get',
            data: {q: $(this).val()},
            dataType: 'json',
            success: function(data) {

               //set this to true when your callback executes
               self.data('active', true);
               //alert(data);
               //set your results into the typehead's source 
               self.data('typeahead').source = data;

               //trigger keyup on the typeahead to make it search
               self.trigger('keyup');
               //All done, set to false to prepare for the next remote query.
               self.data('active', false);
            }
         });
      }
   }
}