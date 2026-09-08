<div class="span10">
    <div class="well">



        <table class="table table-condensed " id="">
            <thead>
            <tr>
                <th>Status</th>
                <th>Project</th>
                <th>Voortgang</th>
                <th>Gebruikers</th>
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

    $(function(){

        $(window).scroll(function() {
            if($(window).scrollTop() == $(document).height() - $(window).height()) {
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
            url: "{LB}/projects/overviewLoader/" + loadFrom
        }).done(function(msg) {
            $('#projectTableBody').append(msg['table_html']);
        });
    }

</script>