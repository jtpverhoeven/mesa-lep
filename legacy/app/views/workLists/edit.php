<div class="span8">
    <div class="well">
        <h3> Werklijst aanpassen </h3>

        {edit_form}


        <div class="pull-left">
            <button id="addColumn" type="button" class="btn btn-small btn-default"><i class="icon-plus"></i> Werklijst kolom toevoegen </button>
        </div>

        <div class="pull-right">
            <button id="saveWorklistButton" type="button" class="btn btn-small btn-primary"><i class="icon-save"></i> Wijzigingen opslaan </button>
        </div>
        <br />
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li><a href="{LB}/workLists/show"><i class="icon-backward"></i> {MESA_ASE_BACKTOLIST}</a> </li>
    </ul>
</div>

<script>
    var colCounter = {col_count};
    var select2options ={ minimumInputLength: 2,
        multiple: true,
        placeholder: "Select a client",

        ajax: {
            type: "POST",
            url: "{LB}/assays/predict/0/0/0",
            dataType: 'json',
            quietMillis: 350,
            data: function (term, page) {
                return {
                    term: term, //search term
                    page_limit: 10 // page size
                };
            },
            results: function (data, page) {
                return {results: data.results};
            }

        },
        initSelection: function (element, callback) {
            return $.getJSON("{LB}/assays/predictInit/" + (element.val()), null, function (data) {
                return callback(data);
            });
        },
        dropdownCssClass: "bigdrop"};

    $(function() {

        $('.summernote').summernote({
          onImageUpload: function(files, editor, welEditable) {
              sendFile(files, editor, welEditable);
          }
        });


        $(".tripcol").select2(select2options);

        $("#analyses").select2(select2options);
        $('#addColumn').on('click', function(){


                var newInput = $("<div class='control-group column-group'> <label class='control-label'>Lijst kolom" + colCounter + "</label><div class='controls'>" +
                    "<input name='column_title_" + colCounter +"' type='text' class='input-block-level' placeholder='Kolom titel'>" +
                    "<input id='column_trip_" + colCounter +"'  name='column_trip_" + colCounter +"' type='text' class='input-block-level' placeholder='Vink aan bij analyse'>");

                $('#extra_pagesCG').before(newInput);
                $('#column_trip_' + colCounter).select2(select2options);
                colCounter = colCounter + 1;
        })

    });

    function sendFile(file,editor,welEditable) {
        data = new FormData();
        data.append("file", file[0]);


        $.ajax({
            data: data,
            type: "POST",
            url: "{LB}/worklists/saveImage",
            cache: false,
            contentType: false,
            processData: false,
            success: function(url) {
                editor.insertImage(welEditable, url);
            }
        });
    }
</script>
