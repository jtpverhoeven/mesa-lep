<div class="well well-small">

    <table width="100%">
        <tr>
            <td style="width: 80px"> <span class="badge" onClick="loadPage('{stamp_previous_day}');"> <i class="icon-backward"></i>  {neat_previous_day}  </span></td>
            <td> <center> 
                        <div class="input-small">                        
                        <input type="text" id="labjournalDate" name="labjournalDate" value="{neat_page_date}" class="input-block-level" placeholder="Select labjournal page date">
                        </div> 
                </center> 
            </td>
            <td style="width: 80px"> <span class="badge {hide_next}" onClick="loadPage('{stamp_next_day}');">  {neat_next_day} <i class="icon-forward"></i> </span></td>
        </tr>
    </table>    
</div>

<div class="well well-small">
    <div class="summernote">{page_data}</div>     
</div>

<div class="well well-small">
    <table style='width: 100%'>
        <tr>
            <td style='text-align: left;'>
                <!-- <button type='button' class='btn btn-small btn-primary'><i class='icon-lock'></i> Private </button>
                <button type='button' class='btn btn-small btn-primary'><i class='icon-comments'></i> Share </button> -->
            </td>
            <td style='text-align: right;'>
                <button type='button' class='btn btn-small btn-success' onClick="saveJournalPage();"><i class='icon-save'></i> Save </button>
                <button type='button' class='btn btn-small btn-primary' onClick="printLabPage();"><i class='icon-print'></i> Print </button>
                
            </td>
        </tr>
    </table>
    
</div>

<script>
    
var pageContent = false;
var pageDate = '{page_date}';

    
//link to opener window
window.setInterval(function(){
 try{
  window.opener.labjournal = window;
 } catch(e){ console.log(e); }
}, 300);



$(document).ready(function() {             
    
    $('#labjournalDate').Zebra_DatePicker({
        format: 'd-m-Y',
        direction: false, 
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,
        onSelect: function(dateAsIs) {
           loadPage(dateAsIs);
        }
    });           
    
    $('.summernote').summernote({         
        height: 400,            
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
            saveJournalPage();
        }, 
        onImageUpload: function(files, editor, welEditable) {
            sendFile(files, editor, welEditable);
        }
   });
});

//note insertion
function addToPage(content){        
    $('.note-editable').append(content);    
}

function saveJournalPage(){
    
    console.log($('.summernote').code());
    
    pageContent = $('.summernote').code();     
    $.ajax({
        type: "POST",
        data: { page_date: pageDate, contents: pageContent },
        url: "{LB}/labJournals/save"
    }).done(function(req) {
        console.log('Labjournal saved');
    });
}


function loadPage(timeStamp){
    window.location.href = '{LB}/labJournals/show/' + timeStamp;
}

function printLabPage(){
     window.location.href = '{LB}/labJournals/printPage/{page_date}';
}

function sendFile(file,editor,welEditable) {
    data = new FormData();        
    data.append("file", file[0]);
       
    
    $.ajax({
        data: data,
        type: "POST",
        url: "{LB}/labJournals/saveImage",
        cache: false,
        contentType: false,
        processData: false,
        success: function(url) {
            editor.insertImage(welEditable, url);
        }
    }); 
}

</script>