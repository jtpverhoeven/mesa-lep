<div id="userInfoWell" class="well well-small">
                 <h6><i class="icon-user"></i> {MESA_UAD_USERINFORMATION} </h6>
                 <div id="userInfo">
                     
                 
<div class="alert {show_lock_warning}">

  <strong><i class="icon-exclamation"></i> {MESA_UAD_ACOUNTDISABLED} </strong>   <br />    
    {MESA_UAD_ACOUNTDISABLEDDESCRIPTION}
</div>
                     
                     
<table class="table-condensed">
    
    <tbody>
        <tr>
            <td><p><strong>{MESA_UAD_USERNAME}</strong></p></td>            
            <td><p>{username} ({id})</p></td>
        </tr>
                
        
        <tr>
            <td><p><strong>{MESA_UAD_NAME}</strong></p></td>            
            <td><p><i class="{sex_icon}"></i> {title} {first_name} {last_name}</p></td>
        </tr>
        
        <tr>
            <td><p><strong>{MESA_UAD_FUNCTION}</strong></p></td>            
            <td><p>{function}</p></td>
        </tr>
        
        <tr>
            <td><p><strong>{MESA_UAD_EMAIL}</strong></p></td>            
            <td><p>{email}</p></td>
        </tr>
        
        <tr>
            <td><p><strong>{MESA_UAD_TELEPHONE}</strong></p></td>            
            <td><p>{phone}</p></td>
        </tr>
    </tbody>
    
</table>
    
<div class="pull-right">
    <button class="btn btn-primary btn-mini" onClick="loadEditForm('{id}');"><i class="icon-pencil"></i> {MESA_UAD_EDIT}</button>
    <button class="btn btn-primary btn-mini" onClick="changePassword('{id}');   "><i class="icon-key"></i> {MESA_UAD_RESETPASSWORD}  </button>
    
    <button class="btn btn-danger btn-mini {show_lock_button}" onClick="lockAccount('{id}', true);"><i class="icon-lock"></i> {MESA_UAD_DISABLE}</button>
    <button class="btn btn-warning btn-mini {show_unlock_button}" onClick="lockAccount('{id}', false);"><i class="icon-unlock"></i> {MESA_UAD_ENABLE}</button>
</div>
<br />

</div>
            </div>
            
             <div class="well well-small">
                 <h6><i class="icon-camera-retro"></i> Avatar </h6>
                 <div style="text-align:center">
                     {avatar}
                 </div>
                 <br />
                 <div class="pull-right">
                    <button class="btn btn-warning btn-mini" onClick="removeAvatar();"><i class="icon-trash"></i> {MESA_UAD_DELETE}</button>
                 </div>
                 <br />
    
                 
            </div>
