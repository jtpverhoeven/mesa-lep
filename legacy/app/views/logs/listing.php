<div class="span3">
    <div class="well">
        <h5><i class="icon-list"></i> {MESA_LOG_LOGS}</h5>

        {file_list}

    </div>
</div>

<div class="span7">
    <div class="well">
        <h5><i class="icon-eye-open"></i> {MESA_LOG_VIEWER}</h5>

        <span id="logLoadingIndicator"><i class="icon-spinner icon-spin"></i> </span>

        <div id="logLoaderWindow">
            <textarea style="width: 100%" rows="20" id="logLoaderTxtWindow"  wrap="soft"></textarea>
        </div>
    </div>
</div>



<script>

$(function(){

    $('#logLoadingIndicator').hide();

    $('#loglisttable').on('click', 'tr', function(){

        $('#logLoadingIndicator').show();
        $('#loglisttable').find('tr').removeClass('success');
        $(this).addClass('success');
        var loadLogFile = $(this).attr('logFileName');
        console.log('Loading:' + loadLogFile);

        $.ajax({
            type: "POST",
            url: "{LB}/logs/load/" + loadLogFile
        }).done(function(logContent) {
            $('#logLoadingIndicator').hide();
            $('#logLoaderTxtWindow').html(logContent);
        });
    });
});

</script>
