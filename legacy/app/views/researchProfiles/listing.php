<div class="span8">
    <div class="well">
        <h3> {MESA_ERP_LISTING} </h3>
        {available_profiles}

    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header">{MESA_ERP_ACTIONS} </li>
        <li><a href="#addProfileModal" role="button"  data-toggle="modal" id="addProfile"><i class="icon-plus"></i> {MESA_ERP_ADD}</a> </li>
        <li><a href="#" id="editProfile"><i class="icon-edit"></i> {MESA_ERP_EDIT} </a> </li>
        <li><a href="#" id="removeProfile"><i class="icon-remove"></i>{MESA_ERP_REMOVE} </a> </li>
        <li><a href="{LB}/researchProfiles/bulkChange" id="bulkChange"><i class="icon-list"></i>Bulk wijziging </a> </li>

    </ul>
</div>


<div id="addProfileModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addProfileModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addProfileModalTitle">{MESA_ERP_ADDTITLE}</h3>
    </div>
    <div class="modal-body" id="addProfileModalBody" >
        {profile_form}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ERP_CANCEL}</button>
        <button class="btn btn-primary" id="saveProfileButton"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_SAVE}</button>
    </div>
</div>

<script>

    $(function() {


        $('#profileTable').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });

     $("#client_name").select2({
            minimumInputLength: 2,
            placeholder: "Select a client",

            ajax: {
            type: "POST",
            url: "{LB}/clients/predictNoSub",
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
           $('#client').val($(this).val());
       });

    $('#global').on('change', function(){

       if($(this).val() == '1'){
           //global
           $('#client_nameCG').hide();
           $('#client_name').val('');
           $('#client').val('');
       }

       if($(this).val() == '0'){
           //client specific
           $('#client_nameCG').show();
           $('#client_name').select2("val", '');
           $('#client').val('');
       }

    });


    $('#editProfile').click(function(){
       var selectedProfile = $('input[name=selectedAnalysis]:checked').val();
       if(selectedProfile !== undefined){
           window.location.href = '{LB}/researchProfiles/edit/' + selectedProfile;
       } else {
           alert('{MESA_ERP_SELECTFIRST}');
       }
    });

    $('#removeProfile').click(function(){
       var selectedProfile = $('input[name=selectedAnalysis]:checked').val();


       if(selectedProfile !== undefined){

            bootbox.confirm("<h3>{MESA_ERP_DELETE}</h3> <p>{MESA_ERP_DELETETEXT}</p>", function(result) {
            if(result == true){
                authPopup('researchProfiles', 'remove', removeGroup, Array(selectedProfile) );
            }

            });

        }else {
            alert('{MESA_ERP_SELECTFIRST}');
        }
    });
});

    function removeGroup(selectedProfile){
        $.ajax({
            type: "POST",
            url: "{LB}/researchProfiles/remove/" + selectedProfile
        }).done(function(msg) {
              //if(msg == '0'){
              //    alert('{MESA_ERP_CANTDELETEINUSE}');
              //} else{
                  window.location.href = '{LB}/researchProfiles/listing';
            //  }
        });
    }

    function saveProfile(){
        $('#addFieldForm').submit();
    }

  


</script>
