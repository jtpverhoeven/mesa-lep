<div class="alert alert-info {misClass}">
  <div class="alert-title"><strong>Dit formulier mist de volgende gegevens</strong></div>
  {missing}
</div>

<table class="table table-bordered table-condensed tab" style="width: 100%;">
    <tr class="{hide_old_block1}">
        <td>Beheer monsteronderzoek door:</td>
        <td><select id="b0_beheer" name="b0_beheer" tabindex="1">
                {beheer_drop}
            </select>
        </td>
    </tr>

    <tr class="{hide_old_block1}">
        <td>Afgewogen door:</td>
        <td><select id="b0_afgewogen" name="b0_afgewogen" tabindex="2">
                {afgewogen_drop}
            </select></td>
    </tr>

    <tr>
        <td>Ingezet door:</td>
        <td><select id="b0_ingezet" name="b0_ingezet" tabindex="3">
                {ingezet_drop}
            </select></td>
    </tr>

    <tr>
        <td>Gegoten door:</td>
        <td><select id="b0_gegoten" name="b0_gegoten" tabindex="4">
                {gegoten_drop}
            </select></td>
    </tr>

    <tr class="{hide_old_block1}">
        <td>Platen in de broedstoof:</td>
        <td><input class="input" type="text" id="b0_instoof" name="b0_instoof" value="{instoof}" tabindex="5" /></td>
    </tr>

</table>

<table class="table table-bordered table-condensed {show_duration_block}" style="width: 100%" >

  <tr>
          <td>Datum uit broedstoof:</td>
          {duration_headers}
  </tr>
  <tr>
          <td>Datum uit broedstoof:</td>
          {duration_out_of_stove}
  </tr>
  <tr>
          <td>Tijd uit broedstoof:</td>
          {duration_time_out_of_stove}
  </tr>
  <tr>
        <td>Datum aflezen:</td>
        {duration_date_readout}
  </tr>
  <tr>
        <td>Tijd aflezen:</td>
        {duration_time_readout}
  </tr>
  <tr>
     <td>Afgelezen door:</td>
        {duration_read_by}
   </tr>

</table>
<!--
<table class="table table-bordered table-condensed" style="width: 100%">

    <tr>
        <td style="width: 25%"><p style="font-weight: bolder;">Aan-/afwezigheid testen</p></td>
        <td style="width: 25%"> <input type="checkbox" {listFlag} disabled> Listeria
                                <input type="checkbox" {salmFlag} disabled> Salmonella
                                <input type="checkbox" {campyFlag} disabled> Campylobacter
                                <input type="checkbox" {stecFlag} disabled> STEC
        </td>
        <td style="width: 20%"><p style="font-weight: bolder;">Temperatuur waterbad legionella</p></td>
        <td style="width: 25%"><input class="" type="text" id="b2_legionella_temp" name="b2_legionella_temp" value="{legionella_temp}" tabindex="31" /> &deg; C</td>
    </tr>
</table> -->

<table class="table table-bordered table-condensed" style="width: 100%">

    <tr>
        <td rowspan="1" style="width: 15%">
            <p style="font-weight: bolder;">Ophopings-/ verdunningsvloeistoffen</p>
        </td>

        <td>THT PFZ</td>
        <td><input class="input-block-level formDate" type="text" id="b2_tht_pfz" name="b2_tht_pfz" value="{tht_pfz}" tabindex="32" />{tht_pfz_explanation_button}</td>

        <td>THT PFZ buizen</td>
        <td><input class="input-block-level formDate" type="text" id="b2_tht_pfz_buizen" name="b2_tht_pfz_buizen" value="{tht_pfz_buizen}" tabindex="33" />{tht_pfz_buizen_explanation_button}</td>        

        <td>THT BPW </td>
        <td><input class="input-block-level formDate" type="text" id="b2_tht_bpw" name="b2_tht_bpw" value="{tht_bpw}" tabindex="37" />{tht_bpw_explanation_button}</td>

    </tr>
    <!--
    <tr>
        <td>THT Fraser</td>
        <td><input class="input-block-level formDate" type="text" id="b2_tht_fraser" name="b2_tht_fraser" value="{tht_fraser}" tabindex="35" /></td>

        <td>THT Bolton broth</td>
        <td><input class="input-block-level formDate" type="text" id="b2_tht_bolton" name="b2_tht_bolton" value="{tht_bolton}" tabindex="36" /></td>
    </tr>
  -->

</table>

<table class="table table-bordered table-condensed" style="width: 100%">

    <tr>
        <td style="width: 49%" colspan="4"><p style="font-weight: bolder;">THT Media</p></td>
    </tr>

    {media_tht_rows}
</table>

<table class="table table-bordered table-condensed" style="width: 100%">

    <tr>
        <td style="width: 49%" colspan="4"><p style="font-weight: bolder;">THT Bevestigings Media</p></td>
    </tr>

    {conf_tht_rows}
</table>

<table class="table table-bordered table-condensed" style="width: 100%">

    <tr>
        <td style="width: 49%" colspan="4"><p style="font-weight: bolder;">Materiaal</p></td>
    </tr>

    {mat_rows}
</table>
