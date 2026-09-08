<!--mpdf

<htmlpageheader name="firstPageHeader">
 <table width="100%" class="addTable" cellspacing="0" cellpadding="0">

        <tr>
            <td width="50%"><img src="<?PHP print ROOT . '/app/docgen/res/logo.jpg'; ?>" width="7.97cm" style="margin-left: 1cm;" /> </td>
            <td width="50%" style="text-align: right;">

            <table width="100%" class="innerAdTable" >

            <tr>
                <td colspan="2">
                   <p style="font-weight: bold;">  Micro-Analyse Zeeland B.V. </p>
                </td>
            </tr>

            <tr>
                <td  colspan="2">
                    Europlein 4
                </td>
            </tr>

            <tr>
                <td>4587 CH  Kloosterzande</td>
                <td>ING: 67.43.23.734</td>
            </tr>
            <tr>
                <td>Tel:  +31 (0)114 68 3093</td>
                <td>KvK nr: 60584904</td>
            </tr>
            <tr>
                <td>Fax: +31 (0)114 68 3091</td>
                <td>BTW: NL853971985B01</td>
            </tr>
            <tr>
                <td>www.microanalyse.nl</td>
                <td>IBAN: NL60INGB0674323734</td>
            </tr>
            <tr>
                <td>info@microanalyse.nl</td>
                <td>BIC: INGBNL2A</td>
            </tr>

            </table>

            </td>
        </tr>
</table>


<hr style="color: #7BC144; height: 1px;"/>


</htmlpageheader>


<htmlpageheader name="followHeader">
<table width="100%">

    <tr>
        <td style="text-align: right;" ><img src="<?PHP print ROOT . '/app/docgen/res/followLogo.jpg';?>" style="width: 2.33cm;"  /></td>
    </tr>

</table>

<hr style="color: #7BC144; height: 1px;"/>

</htmlpageheader>


<htmlpagefooter name="standardFooter">

<table width="100%">

<tr>
    <td style="width: 10cm;  vertical-align: bottom;"><img src="{usr_sig}" style="max-width: 3.5cm;" /></td>
    <td style="text-align: right; vertical-align: bottom;"></td>
</tr>

</table>

<hr style="color: #7BC144; height: 1px;"/>

<table width="100%">

<tr>

    <td style="width: 22cm">
       <p style="font-size: 8px; font-style: italic;">De analyseresultaten hebben uitsluitend betrekking op het/de onderzochte monster(s). Dit rapport mag zonder schriftelijke toestemming van Micro-Analyse Zeeland, niet anders dan in zijn geheel worden gereproduceerd. <br />
        Opmerkingen vermeld op dit certificaat vallen niet onder accreditatie. De prestatiekenmerken van de geaccrediteerde analysemethoden zijn opvraagbaar. Micro-Analyse Zeeland is door de Raad voor Accreditatie (RvA) <br />
        geaccrediteerd volgens ISO 17025 en staat bij de RvA geregistreerd onder nummer L420. <br />
        Legenda: Q = door de RvA geaccrediteerde verrichting, E = door extern laboratorium uitgevoerde verrichting, * = indicatieve waarde, ** = niet bevestigd, (&nbsp;) = referentiewaarden.  <br />
        Resultaten zijn uitgedrukt in kve per gram of ml. van het onderzochte monster, tenzij anders vermeld.</p>
    </td>
    <td style="text-align: right; vertical-align: top;"> <p style="font-size: 8px"> Pagina {PAGENO} van [pagetotal] </p>  </td>




</tr>

</table>

</htmlpagefooter>


<htmlpagefooter name="rvaFooter">

<table width="100%">

<tr>
    <td style="width: 10cm;  vertical-align: bottom;"><img src="{usr_sig}" style="max-width: 3.5cm;" /></td>
    <td style="text-align: right; vertical-align: bottom;">&nbsp;<img src="<?PHP print ROOT . '/app/docgen/res/rva.png';?>" style="width: 1.49cm;" /></td>
</tr>

</table>

<hr style="color: #7BC144; height: 1px;"/>

<table width="100%">

<tr>

    <td style="width: 22cm">
        <p style="font-size: 8px; font-style: italic;">De analyseresultaten hebben uitsluitend betrekking op het/de onderzochte monster(s). Dit rapport mag zonder schriftelijke toestemming van Micro-Analyse Zeeland, niet anders dan in zijn geheel worden gereproduceerd. <br />
        Opmerkingen vermeld op dit certificaat vallen niet onder accreditatie. De prestatiekenmerken van de geaccrediteerde analysemethoden zijn opvraagbaar. Micro-Analyse Zeeland is door de Raad voor Accreditatie (RvA) <br />
        geaccrediteerd volgens ISO 17025 en staat bij de RvA geregistreerd onder nummer L420. <br />
        Legenda: Q = door de RvA geaccrediteerde verrichting, E = door extern laboratorium uitgevoerde verrichting, * = indicatieve waarde, ** = niet bevestigd, (&nbsp;) = referentiewaarden.  <br />
        Resultaten zijn uitgedrukt in kve per gram of ml. van het onderzochte monster, tenzij anders vermeld.</p>
    </td>
    <td style="text-align: right; vertical-align: top;"> <p style="font-size: 8px"> Pagina {PAGENO} van [pagetotal] </p>  </td>

</tr>

</table>

</htmlpagefooter>


mpdf-->

<!--mpdf
<sethtmlpageheader name="firstPageHeader" page="ALL" value="on" show-this-page="1" />
<sethtmlpageheader name="followHeader" page="ALL" value="on" />
<sethtmlpagefooter name="standardFooter" page="ALL" value="on" />

mpdf-->
