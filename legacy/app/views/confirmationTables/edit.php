<div class="span8">
    <div class="well">
        <h3> Bevestigings tabel
        </h3>

        <div id="assayFormDiv">
            {edit_form}
        </div>


        <div class="pull-right">
            <button id="saveTableButton" type="button" class="btn btn-small btn-success"><i class="icon-save"></i> Opslaan</button>
        </div>
        <br />
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li><a href="{LB}/confirmationTables/listing"><i class="icon-backward"></i> {MESA_ASE_BACKTOLIST}</a> </li>
    </ul>
</div>


<script>

    $(function() {

        $('#saveTableButton').on('click', function(){            
        });


        $('.summernote').summernote({
          onImageUpload: function(files, editor, welEditable) {
              sendFile(files, editor, welEditable);
          }
        });

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
