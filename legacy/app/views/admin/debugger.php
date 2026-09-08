<div class="flex flex-col justify-between bg-grey-lighter h-full">

    <div class="flex-none border-b-2 border-black">
        <p class="debug text-center pb-2 pt-2">mesaLIMS debug output</p>
    </div>
    
    <div class="flex-1">
        <div id="scroller" class="h-full  bg-grey-light">
        <div id="debugBody" class='debug text-xs'>
            {debug}
        </div>
        </div>
    </div>
    
    <div class="flex-none">

        <div class="flex justify-between pb-2 pl-2 pr-2">

            
            <div> 
                <button id="refresher" class="bg-transparent hover:bg-blue text-blue-dark font-semibold hover:text-white py-2 px-4 border border-blue hover:border-transparent rounded">
                    Refresh
                </button>
            </div>

            <div> 
                <button id="clear" class="bg-transparent hover:bg-red-light text-blue-dark font-semibold hover:text-white py-2 px-4 border border-blue hover:border-transparent rounded">
                    Clear
                </button>
            </div>

        </div> 

    </div>

</div>

<script>


$(function(){
    
    $('#scroller').slimScroll({
        height: '740px',
        railVisible: true,
        railOpacity: 0.1,
        alwaysVisible: true,           
        start: 'bottom',
    });

    $('#refresher').click(function(){       
        $.ajax({
            type: "POST",
            url: "{LB}/admin/refreshDebugger"
        }).done(function(response) {
            $('#debugBody').html(response);
            var container = $('#scroller');
            container.slimScroll({
                scrollTo: container[0].scrollHeight
            });
        });
    });

    $('#clear').click(function(){       
        $.ajax({
            type: "POST",
            url: "{LB}/admin/clearDebugger"
        }).done(function(response) {
            $('#debugBody').html('');
            $('#refresher').trigger('click');
        });
    });

});

</script>