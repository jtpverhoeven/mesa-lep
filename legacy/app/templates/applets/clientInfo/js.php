<script>

$(function(){

 $("#client_name").select2({
       minimumInputLength: 2,
       placeholder: "{MESA_SAD_SELECTCLIENT}",

       ajax: {
       type: "POST",
       url: "{LB}/clients/predict",
       dataType: 'json',
       quietMillis: 350,
       data: function (term, page) {
           return {
               term: term, //search term
               page_limit: 10 // page size
           };
       },
       results: function (data, page) {
           return { results: data.results };
       }

       },                    
       initSelection: function(element, callback) {
               //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
               //return callback(data);
               //});
       },        
       dropdownCssClass: "bigdrop"
   }).on('change', function(){
        clSplit = $(this).val().split("||");
        selected_client = clSplit[0];
        selected_subclient = clSplit[1];
        
        $('#appletClientInfoLoadSpinner').show();
        
        $.ajax({
            type: "POST",    
            data: {cid: selected_client, scid: selected_subclient},
            url: "{LB}/applets/clientInfoFetch" 
        }).done(function(response) {                       
            $('#appletClientInfoLoadSpinner').hide();       
            $('#appletClientInfoResult').html(response);
        });
        
   });
   

});
    
</script>