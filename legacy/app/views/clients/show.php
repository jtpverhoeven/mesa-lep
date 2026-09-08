

<div class="span8">
        <div class="row-fluid">

            <div class="span4">
                <div class="well">
                    <h5><i class="icon-building"></i> {name}</h5>
                    <address>
                        {street_name} {street_number}<br />
                        {postal_code}, {place}<br />
                        {country}<br />
                        <abbr title="{MESA_CLI_PHONETIP}">{MESA_CLI_PHONEABR}:</abbr> {telephone} <br />
                        <abbr title="{MESA_CLI_EMAILTIP}">{MESA_CLI_EMAILABR}:</abbr> {email} <br />
                    </address>

                    <p>NVWA nummer: {nvwa_number} </p>
                    <p>Debiteur nummer: {debit_number} </p>
                </div>

                    
                <div class="well">
                    <h5><i class="icon-tag"></i> Klant categorie </h5>              
                    {categories}
                </div>


                <div class="well">
                    <h5><i class="icon-user-md"></i> {MESA_CLI_CONTACTPERSON}</h5>
                    <address>
                        <strong>{title} {fname}  {mname} {lname}</strong><br />
                        <abbr title="{MESA_CLI_EMAILTIP}">{MESA_CLI_EMAILABR}:</abbr> {email} <br />
                        <abbr title="{MESA_CLI_PHONETIP}">{MESA_CLI_PHONEABR}:</abbr>   {telephone} <br />
                        <abbr title="GSM nummer">GSM:</abbr>   {cellphone} <br />
                    </address>
                </div>
            

                <div class="well">
                    <h5><i class="icon-signal"></i> {MESA_CLI_STATISTICS} </h5>
                    <h3> {no_samples}<small>  {MESA_CLI_SAMPLES} </small> </h3>
                    <h3> {no_projects}<small> {MESA_CLI_PROJECTS} </small> </h3>
                    <h3> {perc_closed}<small> {MESA_CLI_PROJECTSCLOSED} </small> </h3>
                </div>

            </div>

            <div class="span8">

                <div id="subclientProjects" class="well hide">
                    <span id="subclientInfoSpan"></span>
                    <span id="subclientProjectSpan"></span>
                </div>

                <div id="sampleLockDiv" class="alert alert-block {activate_hider}">
                  <h4>Deze klant is niet actief </h4>
                  Wilt u deze klant terug opnemen in het actieve klanten bestand, klik dan op "klant activeren"
                </div>

                    <div class="well">
                        <h5><i class="icon-smile"></i> Bijzonderheden / wensen klant</h5>
                        <div id="wishesReadOnly">
                            {notes}
                        </div>
                    </div>

                    <div class="well">
                        <h5><i class="icon-paper-clip"></i> Bestanden voor klant </h5>

                        <div id="clientFiles">
                        </div>
                    </div>

                    <div class="well">
                        <h5><i class="icon-file-alt"></i> Standaard rapport instellingen &amp; notities </h5>

                            <!-- <p> Taal:  </p> -->
                            
                            <p> Resultaten in rood: {trip_red} </p> 


                            <p> Notities: {report_notes} </p> 

                            <a href="{LB}/clients/reportSettings/{id}" class="btn btn-mini">Aanpassen </a>

                        <div>
                        </div>
                    </div>

                    <div class="well">
                        <h5><i class="icon-paper-clip"></i> Rapportage PDF's </h5>
                        <div id="reportFiles">
                          {reports}
                        </div>
                    </div>
            </div>
        </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_CLI_CLIACTION} </li>
        <auth:editClientButton>
            <li><a href="{LB}/clients/edit/{id}"><i class="icon-pencil"></i> {MESA_CLI_EDITCLIENTDETAILS} </a> </li>
            <li><a href="#" id="editWishes"><i class="icon-smile" ></i> Bijzonderheden/wensen aanpassen </a> </li>
            <li><a href="#" id="editClientFiles"><i class="icon-paper-clip"></i> Bestanden aanpassen</a> </li>
            <li><a href="{LB}/contactGroups/show/{id}"><i class="icon-group"></i> Contactgroepen bewerken</a> </li>
        </auth>
        
        <auth:clientcats>
                <li><a href="{LB}/clients/editCat/{id}"><i class="icon-tags"></i> Klant categorieën  bewerken</a> </li>
        </auth> 

        <auth:editClientButton>
            <li class="{delete_hider}"><a href="#" id="removeClick"><i class="icon-remove"></i> {MESA_CLI_REMOVECLI} </a> </li>
            <li class="{activate_hider}"><a href="#" id="reactivateClick"><i class="icon-check"></i> Klant activeren </a> </li>
        </auth>

        <li class="nav-header">Klant overzichten </li>
        <li><a href="{LB}/projects/listAllClientProjects/{id}"><i class="icon-suitcase"></i> Projecten </a> </li>
        <li><a href="{LB}/samples/listAllClientSamples/{id}"><i class="icon-beaker"></i> Monsters </a> </li>

        <li class="nav-header">{MESA_CLI_OTHER} </li>
        <li><a href="{LB}/clients/dashboard/"><i class="icon-backward"></i> {MESA_CLI_BACKTODASH}    </a> </li>
        <li><a href="{LB}/clients/listing/"><i class="icon-list"></i> {MESA_CLI_BACKTOLIST} </a> </li>

    </ul>
</div>

<div id="editSubclientModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editSubclientModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editSubclientModalTitle"></h3>
    </div>
    <div class="modal-body" id="editSubclientModalBody" >
        {subclientForm}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_CLI_CANCEL}</button>
        <button class="btn btn-primary" id="saveSubclient"  aria-hidden="true"><i class="icon-save"></i> {MESA_CLI_SAVE}</button>
    </div>
</div>

<div id="editWishesModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editWishesModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editWishesModalTitle">Bijzonderheden / wensen aanpassen</h3>
    </div>
    <div class="modal-body" id="editWishesModalBody" >

        <textarea id="wishesArea" class="summernote"></textarea>

    </div>

    <div class="modal-footer">
        <div class="pull-left">
          <button class="btn btn-danger" id="emptyWishes" aria-hidden="true">Verwijder wensen</button>
        </div>

        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_CLI_CANCEL}</button>
        <button class="btn btn-primary" id="saveWishes" aria-hidden="true"><i class="icon-save"></i> {MESA_CLI_SAVE}</button>
    </div>
</div>


<div id="editFilesModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editFilesModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editFilesModalTitle">Klant bestanden</h3>
    </div>
    <div class="modal-body" id="editFilesModalBody" >

        <h5><i class="icon icon-paper-clip"></i> Klant bestanden</h5>
        <div id="attachedClientFiles">
        </div>

        <h5><i class="icon icon-upload"></i> Nieuw bestand uploaden</h5>
        <div id="newFile">
            <form enctype="multipart/form-data" action="{LB}/clients/saveFile/{id}" method="POST">
                <input id="clientFile" name="clientFile" type="file" /> <br />
                <input id="clientFileUploadButton" type="submit" value="Opslaan" />
            </form>
        </div>


    </div>

    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>
    </div>
</div>

<script>

    var selected_subclient = false;
    var newclient_select = false;

    $(function() {

      $('#reporttable').DataTable( {
      });

      $('.summernote').summernote({

            height: 250,
            toolbar: [
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['table', ['table']],
                ['insert', ['picture']],
                ['view', ['fullscreen', 'codeview']],
            ],
            lang: 'du-NL',
            onblur: function(e) {

            }

        });

        $('#editWishes').on('click ', function(){
            $.ajax({
                dataType: "json",
                type: "POST",
                url: "{LB}/clients/fetchWishes/{id}"
            }).done(function(msg) {
                $('#wishesArea').code(msg['notes']);
                $('#editWishesModal').modal('show');
            });
        });

        $('#saveWishes').on('click', function(){

            $('#editWishesModal').modal('hide');

            var wishesContent =  $('#wishesArea').code();
            $.ajax({
                type: "POST",
                data: { notes: wishesContent},
                url: "{LB}/clients/saveWishes/{id}"
            }).done(function(msg) {
                $('#wishesReadOnly').html(wishesContent);
            });
        });

        $('#emptyWishes').on('click', function(){

            $('#editWishesModal').modal('hide');
            var wishesContent =  '';

            $.ajax({
                type: "POST",
                data: { notes: wishesContent},
                url: "{LB}/clients/saveWishes/{id}"
            }).done(function(msg) {
                $('#wishesReadOnly').html(wishesContent);
            });
        });



        $('#editClientFiles').on('click', function(){
            $.ajax({
                type: "POST",
                url: "{LB}/clients/renderEditFileList/{id}"
            }).done(function(msg) {
                $('#attachedClientFiles').html(msg);
                $('#editFilesModal').modal('show');
            });
        });


        $('#subclientsSpan').on('click', '.list-group-item', function(){
            selected_subclient = $(this).attr('subclient');
            $("#subclientsSpan").find('.list-group-item').removeClass('active');
            $(this).addClass('active');
       });

        $('#addSubClientBtn').on('click', function(){

            newclient_select = true;

            $.ajax({
                type: "POST",
                url: "{LB}/subClients/edit/{id}"
            }).done(function(msg) {
                $('#editSubclientModalBody').html(msg);
                $('#editSubclientModalTitle').html('{MESA_CLI_NEWSUBCLIENT}');
                $('#editSubclientModal').modal('show');
            });
        });

        $('#editSubClientBtn').on('click', function(){

            if(selected_subclient == false){
                alert('{MESA_CLI_SELECTSUBCLIENTFIRST}');
            } else {
                $.ajax({
                    type: "POST",
                    url: "{LB}/subClients/edit/{id}/"  + selected_subclient
                }).done(function(msg) {
                    $('#editSubclientModalBody').html(msg);
                    $('#editSubclientModalTitle').html('{MESA_CLI_EDITSUBCLIENT}');
                    $('#editSubclientModal').modal('show');
                });
            }
        });


        $('#removeSubClientBtn').on('click', function() {

           if(selected_subclient == false){
                alert('{MESA_CLI_SELECTSUBCLIENTFIRST}');
            } else {

                var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);
                bootbox.prompt("<h3>{MESA_CLI_REMOVECLIENTTITLE}</h3> <p>{MESA_CLI_REMOVECLIENTCONFIRM}</p> <p><span class='label label-warning'>" + random_check + "</span>", function(result) {
                    if (result != null) {
                        if (result == random_check) {
                                $.ajax({
                                    type: "POST",
                                    url: "{LB}/subClients/remove/" + selected_subclient
                                }).done(function(msg) {
                                    alert('{MESA_CLI_SUBCLIENTREMOVED}');
                                    reloadSubclientList();
                                });
                        } else {
                            bootbox.alert("<h3>{MESA_CLI_REMOVEERRORTITLE}</h3> <p> {MESA_CLI_REMOVEERROREXPLANATION} </p> ");
                        }
                    }
                });
            }
        });

        $('#reactivateClick').on('click', function() {
          $.ajax({
              type: "POST",
              url: "{LB}/clients/reactivate/{id}"
          }).done(function(msg) {
              alert('Klant gereactiveerd');
              location.reload(true);
          });
        });

        $('#removeClick').on('click', function() {

            var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);
            bootbox.prompt("<h3>{MESA_CLI_REMOVECLIENTTITLE}</h3> <p>{MESA_CLI_REMOVECLIENTCONFIRM}</p> <p><span class='label label-warning'>" + random_check + "</span>", function(result) {
                if (result != null) {
                    if (result == random_check) {
                        $.ajax({
                            type: "POST",
                            url: "{LB}/clients/remove/{id}"
                        }).done(function(msg) {
                            alert('{MESA_CLI_CLIENTREMOVED}');
                            location.reload(true);
                        });
                    } else {
                        bootbox.alert("<h3>{MESA_CLI_REMOVEERRORTITLE}</h3> <p> {MESA_CLI_REMOVEERROREXPLANATION} </p> ");
                    }
                }
            });
        })

        fetchFiles();
    });

    function saveSubClient(){

        var url;

        if(newclient_select == true){
            url  = "{LB}/subClients/doSave";
            newclient_select = false;
        } else {
            url =  "{LB}/subClients/doSave/" + selected_subclient;
        }

        $.ajax({
                type: "POST",
                data: $("#subClientForm").serialize(),
                url: url
            }).done(function(msg) {
                $('#editSubclientModal').modal('hide');
                $('#editSubclientModalBody').html('');
                reloadSubclientList();
            });
    }

    function reloadSubclientList(){

            $.ajax({
                type: "POST",
                url: "{LB}/subClients/renderSubClientList/{id}"
            }).done(function(msg) {
                $('#subclientsSpan').html(msg);
                $('#subclientsWell').highLight();
            });
    }

    function fetchFiles(){
        $.ajax({
            type: "POST",
            url: "{LB}/clients/renderFileList/{id}"
        }).done(function(msg) {
            if(msg == ''){
              $('#clientFiles').html('Geen bestanden');
            }else{
              $('#clientFiles').html(msg);
            }

            $('#clientFiles').highLight();
        });
    }



    function downloadRevision(id){
      revisionPop = window.open('{LB}/exports/downloadRevision/' + id,'revisionPop','height=250,width=250, menubar=no,resizable=no,directories=no,location=no');
      return true;
    }

</script>
