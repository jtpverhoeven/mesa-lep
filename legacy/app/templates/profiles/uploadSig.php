
<p> Handtekening nederlandse rapporten:</p>
<center>
    <img src="data:image/jpeg;base64,{signature}" />
</center>


<p> Handtekening engelse rapporten:</p>
<center>
    <img src="data:image/jpeg;base64,{signature_en}" />
</center>



<form enctype="multipart/form-data" action="{LB}/profiles/saveSignature" method="POST">
    <select id="signatureLanguage" name="signatureLanguage"><option value="nl">Nederlands</option><option value="en">Engels</option></select>
    <input id="signatureProfile" name="signature" type="file" /> <br />
    <input id="signatureUploadButton" type="submit" value="Upload" />
</form>

<a href="{LB}/profiles/removeSignature"> Mijn handtekeningen verwijderen </a>
