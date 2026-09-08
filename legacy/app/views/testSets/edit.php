<div class="span8">
    <div class="well">
        <h3> Test-set bewerken: [{name}]  </h3>

        {render}

    <br />
    <a class="btn btn-primary btn-small" href="{LB}/tests/addTest/{id}">Test toevoegen</a>
    </div>

    
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Testset </li>        
        <li><a href="{LB}/testSets/run/{id}" id="editClick"><i class="icon-play"></i> Uitvoeren </a> </li>               
    </ul>
</div>

<script>
    
    $(function(){

        $('.actionfield').on('change', function(){
            let field = $(this).data('field');
            let value = $(this).val(); 
            let id = $(this).data('action');

            $.ajax({
                type: "POST",
                data: {
                    field : field, 
                    value: value,
                    id : id
                },
                url: "{LB}/testActions/saveField"
            }).done(function(confwindow) {});
            
        });


        $('.testfield').on('change', function(){
            
            let id = $(this).data('id');
            let name = $(this).val(); 

            
            $.ajax({
                type: "POST",
                data: {                    
                    name: name,
                    id : id
                },
                url: "{LB}/tests/changeName"
            }).done(function(confwindow) {});


        });

        $('.doClone').on('click', function(){

            let id = $(this).data('id');
            let tsid = $(this).data('tsid');
            let cloneFrom = $('#cloneOrigin_' + id).val(); 
            window.location = '{LB}/testActions/cloneFromOtherTest/' + tsid +'/' + id + '/' + cloneFrom

        });

        $('.droptest').on('click', function(){
            let id = $(this).data('testid');
            let tsid = $(this).data('tsid');            
            window.location = '{LB}/tests/destroySelf/' + id + '/' + tsid
        });


       

    });
</script>