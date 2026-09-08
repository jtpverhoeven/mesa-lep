<!--mpdf

<htmlpageheader name="firstPageHeader">

    <table width="100%" class="addTable" cellspacing="0" cellpadding="0">
       
        <tr>
            <td width="50%"><img src="<?PHP print ROOT . '/app/docgen/res/logo.jpg';  ?>" width="7.97cm" style="margin-left: 1cm;" /></td>          
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
                <td>KvK nr: 21019915</td>
            </tr>
            <tr>
                <td>Fax: +31 (0)114 68 3091</td>
                <td>BTW: NL809917944B01</td>
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
<hr style="color: #7BC144;
height: 1px;"/>
</htmlpageheader>


<htmlpageheader name="followHeader">

<table width="100%">

    <tr> 
        <td style="text-align: right;" ><img src="<?PHP print ROOT . '/app/docgen/res/followLogo.jpg';?>" style="width: 2.33cm;"  /></td>
    </tr>

</table>

<hr style="color: #7BC144;
height: 1px;"/>
</htmlpageheader>


<htmlpagefooter name="standardFooter">

<table width="100%">

<tr> 
    <td style="width: 10cm">{usr_sig}</td>
    <td style="text-align: right";></td>
</tr>

</table>

<hr style="color: #7BC144; height: 1px;"/>

<table width="100%">

<tr> 

    <td style="width: 14.5cm">
        <p style="font-size: 6px; font-style: italic;"> 
        De analyseresultaten hebben uitsluitend betrekking op het/de onderzochte monster(s). Dit rapport mag zonder schriftelijke toestemming van Micro-Analyse Zeeland, <br /> 		
        niet anders dan in zijn geheel worden gereproduceerd. De versie van dit rapport vervangt eerder uitgegeven versies van hetzelfde referentienummer. <br />
        Opinies en interpretaties vermeld in dit rapport vallen niet onder accreditatie. De prestatiekenmerken van de geaccrediteerde analysemethoden zijn opvraagbaar. <br />
        Micro-Analyse Zeeland B.V. is bij de Raad voor Accreditatie geregistreerd onder registratienummer L420.<br /></p>
    </td>
    <td style="text-align: right; vertical-align: top;"> <p style="font-size: 7px"> Pagina {PAGENO} van [pagetotal] </p>  </td>

</tr>

</table>

</htmlpagefooter>

<htmlpagefooter name="RVAFooter">

<table width="100%">

<tr> 
    <td style="width: 10cm;  vertical-align: bottom;">{usr_sig}</td>
    <td style="text-align: right; vertical-align: bottom;"><img src="<?PHP print ROOT . '/app/docgen/res/rva.png';?>" style="width: 1.49cm;" /></td>
</tr>

</table>

<hr style="color: #7BC144; height: 1px;"/>

<table width="100%">

<tr> 

    <td style="width: 14.5cm">
        <p style="font-size: 6px; font-style: italic;"> 
        De analyseresultaten hebben uitsluitend betrekking op het/de onderzochte monster(s). Dit rapport mag zonder schriftelijke toestemming van Micro-Analyse Zeeland, <br /> 		
        niet anders dan in zijn geheel worden gereproduceerd. De versie van dit rapport vervangt eerder uitgegeven versies van hetzelfde referentienummer. <br />
        Opinies en interpretaties vermeld in dit rapport vallen niet onder accreditatie. De prestatiekenmerken van de geaccrediteerde analysemethoden zijn opvraagbaar. <br />
        Micro-Analyse Zeeland B.V. is bij de Raad voor Accreditatie geregistreerd onder registratienummer L420.<br /></p>
    </td>
    <td style="text-align: right; vertical-align: top;"> <p style="font-size: 7px"> Pagina {PAGENO} van [pagetotal] </p>  </td>

</tr>

</table>

</htmlpagefooter>

mpdf-->

<!--mpdf
    <sethtmlpageheader name="firstPageHeader" page="ALL" value="on" show-this-page="1" />
    <sethtmlpageheader name="followHeader" page="ALL" value="on" />   
mpdf-->



{report_contents}

