<div class="span12" id="confContent">

    {conf_content}

    <hr />
    {conf_note}
    <hr />
    {conf_table}


</div>


<script>


    function updateTHTdates(said, newDate, chainn, mediaId){

        $.ajax({
            type: "POST",
            url: "{LB}/assuranceForms/checkExpiryDate/" + said + '/' + newDate + '/' + mediaId
        }).done(function(msg) {
            $('#conf_' + chainn + '_tht').val(msg);
        });
    }


    function updateAssuranceForm(said, newDate, chainn, mediaId){

        if(newDate === ''){
          newDate = false;
        }

        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/assuranceForms/updateExpiryDate/" + said + '/' + chainn + '/' + newDate + '/' + mediaId
        }).done(function(msg) {
            if(msg['status'] == false){
              alert('Kan THT datum niet opslaan! Dit monster heeft nog geen inzet datum! ');
            }
        });
    }

    $(function() {

        $('.activatorCheckbox').on('click', function(){
            var active = $(this).prop('checked');
            var mediaId = $(this).attr('mediaId');
            var said = $(this).attr('said');
            var globalConf = $(this).attr('globalConf');
            var dF = $(this).attr('df');
            var rep = $(this).attr('rep');

            $.ajax({
                type: "POST",
                data: { said: said, dF:dF, rep:rep, globalConf:globalConf,  active: active, mediaId: mediaId},
                url: "{LB}/confirmations/saveActivator"
            }).done(function(msg) {
            });

          });




        $('.confNote').on('change', function(){

          var id = $(this).attr('confId');
          var note = $(this).val();

          $.ajax({
              type: "POST",
              data: {
                  id: id,
                  note: note,
              },

              url: "{LB}/confirmations/saveNote"
          }).done(function(msg) {

          });
        });

        $('#confContent').on('change', 'input,select', function () {
            var elementId = $(this).attr('id');
            var elementValue = $(this).val();

            

            isExtendoDate = $(this).hasClass('formDate');
            if (isExtendoDate) {
                var currentYear = new Date().getFullYear()
                if (elementValue.length < 6 && elementValue.length !== 0 ) {
                    elementValue = elementValue + '-' + currentYear;
                    $(this).val(elementValue);
                }
            }
        });
    });


</script>
