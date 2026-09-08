<div class="span8">
    <div class="well">
        <h3> Klant categorieën </h3>
        {category_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Categorie acties</li>
        <li><a href="#addCatModal" role="button"  data-toggle="modal" id="addCat"><i class="icon-plus"></i> Categorie toevoegen </a> </li>        
        <li><a href="#" id="showClick"><i class="icon-eye-open"></i> Categorie bekijken </a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> {MESA_ASE_REMOVE}</a> </li>
    </ul>
</div>


<div id="addCatModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addCatModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addCatModalTitle">Categorie toevoegen</h3>
    </div>
    <div class="modal-body" id="addCatModalBody" >

        {addCategoryForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addCategorySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
        
    </div>
</div>

<script>
$(function(){
   
    $('#categoryTable').DataTable(
        {
            "paging":   false,
            "info":     false,        
        }
    );


    $('#removeClick').click(function(){
        
       var selectedCategory = $('input[name=selectedCategory]:checked').val();
       
       if(selectedCategory !== undefined)
       {
            bootbox.confirm("<h3>Categorie verwijderen?</h3> <p>Weet u zeker dat u deze categorie wilt verwijderen?</p>", function(result) {
                if(result == true){
                    $.ajax({
                        type: "POST",
                        url: "{LB}/clientCategories/destroy/" + selectedCategory
                    }).done(function(confwindow) {
                        $('#tr_' + selectedCategory).remove();
                    });
                }
            });
       } 
       
       else 
       {
           alert('Geen categorie geselecteerd');
       }
    });

    $('#showClick').click(function(){
        
        var selectedCategory = $('input[name=selectedCategory]:checked').val();
       
        if(selectedCategory !== undefined)
        {
            window.location.href = '{LB}/clientCategories/show/' + selectedCategory;
        } 
        
        else 
        {
            alert('Geen categorie geselecteerd');
        }

    });

});

</script>