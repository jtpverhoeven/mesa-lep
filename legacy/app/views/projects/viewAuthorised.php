<div class="span10">
    <div class="well">



        <table class="table table-condensed " id="">
            <thead>
            <tr>
                <th>Status</th>
                <th>Project</th>
                <th>Klant referentie</th>
                <th>Aantal monsters <br />in project</th>
                <th>Type <br /> monster(s)</th>
                <th class="hidden">Voortgang</th>
                <th class="hidden">Gebruikers</th>
                <th>Geauthoriseerd op</th>
                <th style="text-align: right;">Acties</th>
            </tr>
            </thead>
            <tbody id="projectTableBody">

            </tbody>
        </table>

    </div>
</div>

<script>

    var lastLoaded = false;
    var initialLoad = false; 

    $(function(){

        $(window).scroll(function() {

        var limit = Math.max( document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight) - window.innerHeight; 

        if(Math.ceil(window.pageYOffset) >= limit && initialLoad) {                
            loadList();
        }

        });

        loadList();
    });

    function loadList(){
        var loadFrom = $('#projectTableBody tr').length;
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/projects/overviewLoader/" + loadFrom + "/0/1"
        }).done(function(msg) {
            initialLoad = true; 
            lastLoaded = msg['last_id'];
            $('#projectTableBody').append(msg['table_html']);
        });
    }

</script>
